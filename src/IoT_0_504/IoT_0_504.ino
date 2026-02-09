#include "RTC.h"
#include <WiFiS3.h>
#include <NTPClient.h>
#include <WiFiUdp.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <DHT.h>
#include <SPI.h>
#include <Arduino.h>
#include <U8g2lib.h>

// --- OLED設定 ---
U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, /* reset=*/ U8X8_PIN_NONE);

// --- ピン定義 ---
#define VIBRATION_PIN 3
#define BUZZER_PIN 9
#define DHTPIN 2
#define DHTTYPE DHT11
#define LIGHT_SENSOR_PIN A0

DHT dht(DHTPIN, DHTTYPE);
const int chipSelectPin = 10;
SPISettings scp1000Settings(1000000, MSBFIRST, SPI_MODE0);

// --- ネットワーク・定数 ---
const char *ssid = "oyama-android";
const char *pass = "oyama.android";
const char server_ip[] = "10.100.56.161";
IPAddress local_IP(10, 100, 56, 172);
IPAddress gateway(10, 100, 56, 254);
IPAddress subnet(255, 255, 255, 0);

WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "10.100.56.161", 32400);
WiFiClient client;

// --- 設定値 ---
const int light_threshold = 100;
const char *room = "0-506";
const int send_time = 3600;
const int vibration_threshold = 0;
const int vibrationsensor_lowtimeout = 10000;
const unsigned long VIBRATION_TIMEOUT = 10000;
unsigned long lastUpdate = 0;

// --- グローバル変数 ---
volatile bool alarm_fired = false;
volatile bool vibration_detected = false;
volatile int vibration_count = 0;
unsigned long last_vibration_time = 0;
bool is_vibrating = false;
unsigned long low_start_time = 0;

char buf[64];
float t = 0, h = 0, p = 0;
bool l = false;

// --- プロトタイプ宣言 ---
void connectWiFi();
void syncTimeNTP();
void getData();
void wakeUpVibration();
void sendData(bool type);
void printCurrentTime();
void setNextIntervalAlarm(int intervalSeconds);
void alarm_callback();
void handleVibrationEvent();
void handleTimerEvent();
void writeRegister(byte registerAddress, byte value);
long readRegister(byte registerAddress, int numBytes);
void dispOLED_env();
void dispOLED_vib();
void dispOLED_vibsensor_warn();

void setup() {
  Serial.begin(115200);

  pinMode(VIBRATION_PIN, INPUT_PULLUP);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(chipSelectPin, OUTPUT);
  digitalWrite(chipSelectPin, HIGH);

  dht.begin();
  RTC.begin();
  SPI.begin();
  u8g2.begin();
  u8g2.setFont(u8g2_font_ncenB08_tr); // フォントを設定

  connectWiFi();
  syncTimeNTP();
  setNextIntervalAlarm(send_time);
  attachInterrupt(digitalPinToInterrupt(VIBRATION_PIN), wakeUpVibration, FALLING);

  writeRegister(0x03, 0x0A);
  delay(100);
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
    syncTimeNTP();
    delay(1000);
  }

  if (millis() - lastUpdate > 1000) { // 1秒ごとにデータを更新
    getData();
    syncTimeNTP();
    lastUpdate = millis();
  }

  int pinStatus = digitalRead(VIBRATION_PIN);
  if (pinStatus == LOW) {
    if (low_start_time == 0) low_start_time = millis();
    if (millis() - low_start_time > vibrationsensor_lowtimeout) {
      Serial.println("vibrationsensor_low");
      dispOLED_vibsensor_warn();
    }
  } else {
    low_start_time = 0;
    if (!is_vibrating) dispOLED_env();
  }

  if (vibration_detected) {
    handleVibrationEvent();
    vibration_detected = false;
  }

  if (alarm_fired) {
    handleTimerEvent();
    alarm_fired = false;
    setNextIntervalAlarm(send_time);
  }
  
  delay(100);
}

void dispOLED_env() {
  RTCTime now;
  RTC.getTime(now);
  
  u8g2.clearBuffer();          
  u8g2.setFont(u8g2_font_6x10_tr); 

  // 1行目：日時 (y=9)
  char timestr[20];
  sprintf(timestr, "%04d/%02d/%02d %02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes());
  u8g2.setCursor(0, 9);
  u8g2.print(timestr);

  // 2行目：区切り線 (y=15)
  u8g2.setCursor(0, 15);
  u8g2.print("---------------------");

  // 3行目：部屋番号 (y=23)  <-- ここを26から23へ引き上げ
  u8g2.setCursor(0, 23);
  u8g2.print("Room : ");
  u8g2.print(room);

  // 4行目：室温 (y=32)
  u8g2.setCursor(0, 32);
  u8g2.print("Temp : ");
  u8g2.print(t, 1);
  u8g2.print(" C");

  // 5行目：湿度 (y=41)
  u8g2.setCursor(0, 41);
  u8g2.print("Humid: ");
  u8g2.print(h, 1);
  u8g2.print(" %");

  // 6行目：気圧 (y=50)
  u8g2.setCursor(0, 50);
  u8g2.print("Pres : ");
  u8g2.print(p, 1);
  u8g2.print(" hPa");

  // 7行目：照度 (y=59)
  u8g2.setCursor(0, 59);
  u8g2.print("Light: ");
  u8g2.print(l ? "Bright":"Dark"); 
  /*
  u8g2.print((float)analogRead(LIGHT_SENSOR_PIN), 1); 
  */
  u8g2.sendBuffer(); 
}

void dispOLED_vib() {
  u8g2.clearBuffer(); 
  u8g2.setCursor(0, 9);
  u8g2.print("Vibration Detected!!!");
  u8g2.sendBuffer();
}

void dispOLED_vibsensor_warn() {
  u8g2.clearBuffer(); 
  u8g2.setCursor(0, 9);
  u8g2.print("Warning : Vib LOW!");
  u8g2.sendBuffer();
  
}

void getData() {
  h = dht.readHumidity();
  t = dht.readTemperature();
  unsigned long msb = readRegister(0x1F, 1);
  unsigned long lsb = readRegister(0x20, 2);
  unsigned long pressureRaw = ((msb & 0x07) << 16) | lsb;
  p = (pressureRaw / 4.0) / 100.0;
  l = (analogRead(LIGHT_SENSOR_PIN) > light_threshold) ? true : false;
}

void printCurrentTime() {
  RTCTime now;
  RTC.getTime(now);
  sprintf(buf, "%04d-%02d-%02d %02d:%02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes(), now.getSeconds());
}

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.config(local_IP, gateway, subnet);
  WiFi.begin(ssid, pass);
  int timeout = 0;
  while (WiFi.status() != WL_CONNECTED && timeout < 20) {
    delay(1000);
    timeout++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("WiFi Connected");
  } else {
    Serial.println("WiFi Connection Failed");
  }
}

void syncTimeNTP() {
  if (WiFi.status() != WL_CONNECTED) return;
  timeClient.begin();
  if (timeClient.update()) {
    RTCTime currentTime(timeClient.getEpochTime());
    RTC.setTime(currentTime);
  }
  Serial.println("NTP Time Synchronized");
}

void sendData(bool type) {
  if (WiFi.status() != WL_CONNECTED) return;
  
  if (client.connect(server_ip, 80)) {
    StaticJsonDocument<200> doc;
    doc["type"] = "Arduino";
    doc["datetime"] = buf; // ここに入る値が最新か確認が必要
    doc["room"] = room;
    if (type) {
      doc["temp"] = t;
      doc["humid"] = h; 
      doc["press"] = p; 
      doc["light"] = l;
    } else {
      doc["quake"] = (int)vibration_count;
    }

    String jsonString;
    serializeJson(doc, jsonString);

    // HTTPリクエストの送信
    client.println("POST /api/api_main.php HTTP/1.1");
    client.print("Host: "); client.println(server_ip);
    client.println("Content-Type: application/json");
    client.print("Content-Length: "); client.println(jsonString.length());
    client.println("Connection: close");
    client.println(); // 正しい空行
    client.print(jsonString);
    
    // 送信完了を待つためのディレイ（重要）
    delay(100);     
    client.stop();

    Serial.println(jsonString);
  }
}

void setNextIntervalAlarm(int intervalSeconds) {
  RTCTime now;
  RTC.getTime(now);
  unsigned long nextEpoch = ((now.getUnixTime() / intervalSeconds) + 1) * intervalSeconds;
  RTCTime alarmTime(nextEpoch);
  AlarmMatch match;
  match.addMatchDay(); match.addMatchHour(); match.addMatchMinute(); match.addMatchSecond();
  RTC.setAlarmCallback(alarm_callback, alarmTime, match);
}

void alarm_callback() { alarm_fired = true; }

void writeRegister(byte registerAddress, byte value) {
  byte address = registerAddress << 2 | 0x02;
  SPI.beginTransaction(scp1000Settings);
  digitalWrite(chipSelectPin, LOW);
  SPI.transfer(address);
  SPI.transfer(value);
  digitalWrite(chipSelectPin, HIGH);
  SPI.endTransaction();
}

long readRegister(byte registerAddress, int numBytes) {
  byte address = registerAddress << 2;
  long result = 0;
  SPI.beginTransaction(scp1000Settings);
  digitalWrite(chipSelectPin, LOW);
  SPI.transfer(address);
  for (int i = 0; i < numBytes; i++) result = (result << 8) | SPI.transfer(0x00);
  digitalWrite(chipSelectPin, HIGH);
  SPI.endTransaction();
  return result;
}

void wakeUpVibration() { vibration_detected = true; }

void handleVibrationEvent() {
  is_vibrating = true;
  vibration_count = 0;
  RTCTime startTime;
  RTC.getTime(startTime);
  last_vibration_time = millis();
  bool last_pin_state = HIGH;

  while (millis() - last_vibration_time <= VIBRATION_TIMEOUT) {
    bool current_pin_state = digitalRead(VIBRATION_PIN);
    if (current_pin_state == LOW && last_pin_state == HIGH) {
      vibration_count++;
      last_vibration_time = millis();
      digitalWrite(BUZZER_PIN, HIGH);
      delay(50);
      digitalWrite(BUZZER_PIN, LOW);
    }
    last_pin_state = current_pin_state;
    if (alarm_fired) {
      handleTimerEvent();
      alarm_fired = false;
      setNextIntervalAlarm(send_time);
    }
    Serial.print(".");
    dispOLED_vib();
    delay(1);
  }
  
  if (vibration_count > vibration_threshold) {
    sprintf(buf, "%04d-%02d-%02d %02d:%02d:%02d",
            startTime.getYear(), (int)startTime.getMonth() + 1, startTime.getDayOfMonth(),
            startTime.getHour(), startTime.getMinutes(), startTime.getSeconds());
    sendData(false);
  }
  is_vibrating = false;
}

void handleTimerEvent() {
  printCurrentTime();
  getData();
  sendData(true);
}