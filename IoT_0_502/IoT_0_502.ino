#include "RTC.h"
#include <WiFiS3.h>
#include <NTPClient.h>
#include <WiFiUdp.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <DHT.h>
#include <SPI.h>

// ピン定義
#define VIBRATION_PIN 3  // 振動センサ SW-420
#define BUZZER_PIN    9  // ブザー
#define DHTPIN        2  // 温湿度センサ DHT11
#define DHTTYPE       DHT11
#define LIGHT_SENSOR_PIN A0 // 照度センサ NJL7502L

// OLED設定
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// センサ・通信用インスタンス
DHT dht(DHTPIN, DHTTYPE);
const int chipSelectPin = 10;
SPISettings scp1000Settings(1000000, MSBFIRST, SPI_MODE0);

// ネットワーク・定数
const char* ssid = "oyama-android"; 
const char* pass = "oyama.android"; 
const char server_ip[] = "10.100.56.161";
IPAddress local_IP(10, 100, 56, 170);
IPAddress gateway(10, 100, 56, 254);
IPAddress subnet(255, 255, 255, 0);
const int light_threshold = 100;
const char* room = "0-502";
const int send_time = 10;

// グローバル変数
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "10.100.56.161", 32400); 
WiFiClient client;
volatile bool alarm_fired = false;
bool first_boot = true;
bool vibration_detected = false;

// 振動用変数
bool v; // 振動フラグ
volatile int vibration_count = 0; // 振動のカウント数(振動レベル送信データ)
const volatile int vibration_threshold = 0; // 振動レベルの閾値
unsigned long last_vibration_time = 0; // 最後に振動を検知した時刻
bool is_vibrating = false; // 現在「揺れている最中」かどうかのフラグ
const unsigned long VIBRATION_TIMEOUT = 2000; // 1分間振動がなければ収束とみなす


// 送信用データの格納
char buf[64]; // 時刻
float t, h, p; // 温度、湿度、気圧
int l; // 照度(true,false)

// 照度値の格納(後で消す)
int lightValue;

// プロトタイプ宣言
void connectWiFi();
void syncTimeNTP();
void getData(); // データ取得
void dispOLED(); // OLEDへの表示
void wakeUpVibration();
void alertBuzzer();
void sendData(bool type);
void printCurrentTime();
void setNextIntervalAlarm(int intervalSeconds);
void alarm_callback();
void handleVibrationEvent(); // 振動が発生した時の処理
void handleTimerEvent(); // 定刻になった時の処理
void writeRegister(byte registerAddress, byte value);
long readRegister(byte registerAddress, int numBytes); 

void setup() {
  Serial.begin(115200);
  
  // OLEDの初期化はここだけでOK
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
    for(;;); 
  }
  
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.display(); // 一旦クリアを反映

  pinMode(VIBRATION_PIN, INPUT_PULLUP);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(chipSelectPin, OUTPUT);
  digitalWrite(chipSelectPin, HIGH);
  
  dht.begin();
  RTC.begin();
  SPI.begin();

  connectWiFi();
  syncTimeNTP();
  WiFi.disconnect();
  
  setNextIntervalAlarm(send_time);
  attachInterrupt(digitalPinToInterrupt(VIBRATION_PIN), wakeUpVibration, FALLING);
  
  writeRegister(0x03, 0x0A); 
  delay(100);
}
void loop() {
  // --- スリープ実行 ---
  Serial.println("Entering Deep Sleep...");
  delay(100); 
  while (!alarm_fired && !vibration_detected) {
    // 省電力待機（割り込みが入るとここを抜ける）
  }

  // --- 復帰後の処理 ---
  // --- 振動感知の時 ---
  if (vibration_detected) {
    handleVibrationEvent();
    vibration_detected = false;
  }

  // --- 時刻おきの処理 ---
  if (alarm_fired) {
    handleTimerEvent();
    alarm_fired = false;
    setNextIntervalAlarm(send_time);
  }
}

// データ取得
void getData() {
  // DHT 温度、湿度取得
  h = dht.readHumidity();
  t = dht.readTemperature();

  // SCP1000 気圧取得
  //unsigned long msb = readRegister(0x1F, 1);
  //unsigned long lsb = readRegister(0x20, 2);
  //unsigned long pressureRaw = ((msb & 0x07) << 16) | lsb;
  //p = (pressureRaw / 4.0) / 100.0;
  p = 6.9;
  // 照度取得
  int lightValue = analogRead(LIGHT_SENSOR_PIN);
  l = (lightValue > light_threshold) ? 1 : 0;

  // 振動状態確定
  v = vibration_detected;
  Serial.println("Data collected from sensors.");
}

// OLEDへの表示
void dispOLED() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("Environmental Data");
  display.println("--------------------");

  if (isnan(h) || isnan(t)) {
    display.println("DHT11: Error");
  } else {
    display.print("Temp:  "); display.print(t, 1); display.println(" C");
    display.print("Hum:   "); display.print(h, 1); display.println(" %");
  }

  if (p < 500) {
    display.println("Baro:  Error");
  } else {
    display.print("Press: "); display.print(p, 1); display.println(" hPa");
  }

  // 追加: 振動状態の表示
  display.print("Vib:    ");
  display.println(v ? "ALARM!" : "OK");

  // --- 照明状態の表示 ---
  display.print("Light: ");
  display.print("Status: ");
  if (l) {
    display.println("ON (Bright)");
  } else {
    display.println("OFF (Dark)");
  }

  display.display();
  delay(2000); 
}

// 圧電ブザーを鳴らす
void alertBuzzer() {
  Serial.println("!!! Vibration Detected - Alerting !!!");
  for(int i=0; i<3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(200);
    digitalWrite(BUZZER_PIN, LOW);
    delay(100);
  }
}

// 時刻取得
void printCurrentTime() {
  RTCTime now;
  RTC.getTime(now);
  sprintf(buf, "%04d-%02d-%02d %02d:%02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes(), now.getSeconds());
  Serial.println(buf);
}

// Wifi接続
void connectWiFi() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.config(local_IP, gateway, subnet);
    Serial.print("Connecting to WiFi...");
    WiFi.begin(ssid, pass);
    int timeout = 0;
    while (WiFi.status() != WL_CONNECTED && timeout < 20) { 
      delay(1000); 
      Serial.print("."); 
      timeout++; 
    }
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nConnected!");
    }
  }
}

// 時刻同期
void syncTimeNTP() {
  if (WiFi.status() == WL_CONNECTED) {
    timeClient.begin();
    Serial.print("Syncing NTP...");
    if (timeClient.update()) {
      RTCTime currentTime(timeClient.getEpochTime());
      RTC.setTime(currentTime);
      Serial.println(" Success!");
    } else {
      Serial.println(" Failed.");
    }
  }
}

// データ送信 (true:温度、湿度、気圧、照度 false:振動レベル)
void sendData(bool type) {
  if (client.connect(server_ip, 80)) {
    StaticJsonDocument<200> doc;
    doc["type"] = "Arduino"; 
    doc["datetime"] = buf;
    doc["room"] = room;
    
    if (type) {
      doc["temp"] = t;
      doc["humid"] = h;
      doc["press"] = p;
      doc["light"] = l;
    } else {
      doc["quake"] = (int) vibration_count;
    }

    String jsonString;
    serializeJson(doc, jsonString);
    client.println("POST /api/api_main.php HTTP/1.1");
    client.print("Host: "); client.println(server_ip); // Hostヘッダーを追加
    client.println("Content-Type: application/json");   // これが重要
    client.print("Content-Length: "); client.println(jsonString.length());
    client.println("Connection: close");
    client.println();
    client.print(jsonString);
    client.stop();
    Serial.println("JSON Sent.");
  }
}

// 送る時間間隔設定
void setNextIntervalAlarm(int intervalSeconds) {
  RTCTime now;
  RTC.getTime(now);
  unsigned long currentEpoch = now.getUnixTime();
  unsigned long nextEpoch = ((currentEpoch / intervalSeconds) + 1) * intervalSeconds;
  RTCTime alarmTime(nextEpoch);
  AlarmMatch match;
  match.addMatchDay(); match.addMatchHour(); match.addMatchMinute(); match.addMatchSecond();
  RTC.setAlarmCallback(alarm_callback, alarmTime, match);
}

// 定刻割り込み
void alarm_callback() {
  alarm_fired = true;
}

// SCP1000への書込みレジスタ
void writeRegister(byte registerAddress, byte value) {
  byte address = registerAddress << 2 | 0x02;
  SPI.beginTransaction(scp1000Settings);
  digitalWrite(chipSelectPin, LOW);
  SPI.transfer(address);
  SPI.transfer(value);
  digitalWrite(chipSelectPin, HIGH);
  SPI.endTransaction();
}

// SCP1000への読込みレジスタ
long readRegister(byte registerAddress, int numBytes) {
  byte address = registerAddress << 2;
  long result = 0;
  SPI.beginTransaction(scp1000Settings);
  digitalWrite(chipSelectPin, LOW);
  SPI.transfer(address);
  for (int i = 0; i < numBytes; i++) {
    result = (result << 8) | SPI.transfer(0x00);
  }
  digitalWrite(chipSelectPin, HIGH);
  SPI.endTransaction();
  return result;
}

// 振動用割り込み用関数
void wakeUpVibration() {
  vibration_detected = true;
}

// 振動が発生した時の処理
void handleVibrationEvent() {
  is_vibrating = true;
  vibration_count = 0;
  
  // 揺れが収まるまでカウント
  while (true) {
    if (digitalRead(VIBRATION_PIN) == LOW) {
      Serial.print(".");
      vibration_count++;
      last_vibration_time = millis();
      delay(10); 
    }
    if (millis() - last_vibration_time > VIBRATION_TIMEOUT) break;
  }

  // 振動レベルの閾値を超えたらデータ送信
  if (vibration_count > vibration_threshold) {
    alertBuzzer();
    connectWiFi();
    printCurrentTime();
    sendData(false);
    WiFi.disconnect();
  }
  
}

// 定刻になった時の処理
void handleTimerEvent() {
  connectWiFi();
  printCurrentTime();
  getData(); 
  dispOLED();
  sendData(true);
  WiFi.disconnect();
}