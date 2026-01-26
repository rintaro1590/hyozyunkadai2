#include "RTC.h"
#include <WiFiS3.h>
#include <NTPClient.h>
#include <WiFiUdp.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <DHT.h>
#include <SPI.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// --- OLED設定 ---
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

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
IPAddress local_IP(10, 100, 56, 170);
IPAddress gateway(10, 100, 56, 254);
IPAddress subnet(255, 255, 255, 0);

WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "10.100.56.161", 32400);
WiFiClient client;

// --- 設定値 ---
const int light_threshold = 100;
const char *room = "0-504";
const int send_time = 3600;
const int vibration_threshold = 80;
const int vibrationsensor_lowtimeout = 2000;
const unsigned long VIBRATION_TIMEOUT = 10000;

// --- グローバル変数 ---
volatile bool alarm_fired = false;
volatile bool vibration_detected = false;
volatile int vibration_count = 0;
unsigned long last_vibration_time = 0;
bool is_vibrating = false;
unsigned long low_start_time = 0;

char buf[64];
float t = 0, h = 0, p = 0;
int l = 0;

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
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  }
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);

  pinMode(VIBRATION_PIN, INPUT_PULLUP);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(chipSelectPin, OUTPUT);
  digitalWrite(chipSelectPin, HIGH);

  dht.begin();
  RTC.begin();
  SPI.begin();

  connectWiFi();
  syncTimeNTP();
  getData();
  setNextIntervalAlarm(send_time);
  attachInterrupt(digitalPinToInterrupt(VIBRATION_PIN), wakeUpVibration, FALLING);
  dispOLED_env();

  writeRegister(0x03, 0x0A);
  delay(100);
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  int pinStatus = digitalRead(VIBRATION_PIN);
  if (pinStatus == LOW) {
    if (low_start_time == 0) low_start_time = millis();
    if (millis() - low_start_time > vibrationsensor_lowtimeout) {
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
  static unsigned long lastUpdate = 0;
  if (millis() - lastUpdate > 2000) { // 2秒ごとにデータを更新
    getData();
    lastUpdate = millis();
  }
  delay(100);
}

void dispOLED_env() {
  RTCTime now;
  RTC.getTime(now);
  display.clearDisplay();
  display.setCursor(0, 0);
  char timeStr[20];
  sprintf(timeStr, "%04d/%02d/%02d %02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes());
  display.println(timeStr);
  display.println("---------------------");
  display.print("room : "); display.println(room);
  display.print("Temp : "); display.print(t, 1); display.println(" C");
  display.print("Hum  : "); display.print(h, 1); display.println(" %");
  display.print("Pres : "); display.print(p, 1); display.println(" hPa");
  display.print("LightVal:"); display.println((int)analogRead(LIGHT_SENSOR_PIN));
  display.print("Light: ");
  display.println(l ? "bright" : "dark");
  display.display();
}

void dispOLED_vib() {
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("\n---------------------\n\nVibration Detected!!!");
  display.display();
}

void dispOLED_vibsensor_warn() {
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("\n---------------------\n\nWarning : Vib LOW!");
  display.display();
}

void getData() {
  h = dht.readHumidity();
  t = dht.readTemperature();
  unsigned long msb = readRegister(0x1F, 1);
  unsigned long lsb = readRegister(0x20, 2);
  unsigned long pressureRaw = ((msb & 0x07) << 16) | lsb;
  p = (pressureRaw / 4.0) / 100.0;
  l = (analogRead(LIGHT_SENSOR_PIN) > light_threshold);
}

void printCurrentTime() {
  RTCTime now;
  RTC.getTime(now);
  sprintf(buf, "%04d-%02d-%02d %02d:%02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes(), now.getSeconds());
  Serial.println(buf);
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
}

void syncTimeNTP() {
  if (WiFi.status() != WL_CONNECTED) return;
  timeClient.begin();
  if (timeClient.update()) {
    RTCTime currentTime(timeClient.getEpochTime());
    RTC.setTime(currentTime);
  }
}

void sendData(bool type) {
  if (WiFi.status() != WL_CONNECTED) return;
  if (client.connect(server_ip, 80)) {
    StaticJsonDocument<200> doc;
    doc["type"] = "Arduino";
    doc["datetime"] = buf;
    doc["room"] = room;
    if (type) {
      doc["temp"] = t; doc["humid"] = h; doc["press"] = p; doc["light"] = l;
    } else {
      doc["quake"] = (int)vibration_count;
    }
    String jsonString;
    serializeJson(doc, jsonString);
    client.println("POST /api/api_main.php HTTP/1.1");
    client.print("Host: "); client.println(server_ip);
    client.println("Content-Type: application/json");
    client.print("Content-Length: "); client.println(jsonString.length());
    client.println("Connection: close\n");
    client.print(jsonString);
    client.stop();
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