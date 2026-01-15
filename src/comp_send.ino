#include "RTC.h"
#include <WiFiS3.h>
#include <NTPClient.h>
#include <WiFiUdp.h>
#include <ArduinoJson.h>

const char* ssid = "oyama-android";     
const char* pass = "oyama.android";     
const char server_ip[] = "10.100.56.161";

WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "10.100.56.161", 32400); 
WiFiClient client;
volatile bool alarm_fired = false;
bool first_boot = true; // 起動直後の送信を判定するフラグ

IPAddress local_IP(10,100,56,170);
IPAddress gateway(10,100,56,254);
IPAddress subnet(255, 255, 255, 0);   

char buf[64]; 
float t, h, p; // getDataで更新
boolean l; 

void setup() {
  Serial.begin(115200);
  delay(2000); 

  Serial.println("\n--- System Booting ---");
  RTC.begin();

  connectWiFi();
  syncTimeNTP();
}

void loop() {
  // 次の1時間単位（XX:00:00）にアラームをセット
  //setNextIntervalAlarm(3600); 
  // 次の1時間単位（XX:00:+10）にアラームをセット
  setNextIntervalAlarm(10); 

  // 起動直後の場合、送信せずにすぐ待機モードに入る
  if (first_boot) {
    Serial.println("Initial wait for the next scheduled time...");
    first_boot = false; 
  } else {
    // 2回目（アラーム発火後）から実行される処理
    Serial.println("--- Scheduled Task Start ---");
    printCurrentTime(); 
    getData();
    dispOLED(); 
    sendData(); 
    Serial.println("Task Completed.");
  }

  Serial.println("Waiting for next alarm...");
  Serial.flush();

  alarm_fired = false;
  while (!alarm_fired) {
    delay(100); // 待機
  }
  Serial.println("--- Wake up ---");
}

void getData(){ // ここを書いてほしい
  t = 24.3;
  h = 43.2;
  p = 1013.2;
  l = true;
  Serial.println("Data collected from sensors.");
}

void dispOLED(){ //ここを書いてほしい
 Serial.println("OLED");
}

// --- 以下、既存の関数（connectWiFi, syncTimeNTP, sendData等）はそのまま ---
// ※ printCurrentTime の sprintf だけ、buf を dispOLED でも使うので重要です。

void printCurrentTime() {
  RTCTime now;
  RTC.getTime(now);
  sprintf(buf, "%04d-%02d-%02d %02d:%02d:%02d",
          now.getYear(), (int)now.getMonth() + 1, now.getDayOfMonth(),
          now.getHour(), now.getMinutes(), now.getSeconds());
  Serial.println(buf);
}

void connectWiFi() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.config(local_IP, gateway, subnet);
    Serial.print("Connecting to WiFi...");
    WiFi.begin(ssid, pass);
    int timeout = 0;
    while (WiFi.status() != WL_CONNECTED && timeout < 20) { delay(1000); Serial.print("."); timeout++; }
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nConnected!");
    }
  }
}

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

void sendData(){
  if (client.connect(server_ip, 80)) {
    StaticJsonDocument<200> doc;
    doc["type"] = "Arduino";
    doc["datetime"] = buf;
    doc["room"] = "O-502";
    doc["temp"] = t;
    doc["humid"] = h;
    doc["press"] = p;
    doc["light"] = l;
    String jsonString;
    serializeJson(doc, jsonString);
    client.println("POST /api/api_main.php HTTP/1.1");
    client.print("Host: "); client.println(server_ip);
    client.println("Content-Type: application/json");
    client.print("Content-Length: "); client.println(jsonString.length());
    client.println("Connection: close");
    client.println();
    client.print(jsonString);
    client.stop();
    Serial.println("JSON Sent.");
  }
}

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

void alarm_callback() {
  alarm_fired = true;
}