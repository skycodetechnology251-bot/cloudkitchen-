#!/bin/bash
mkdir -p out
for p in "in.dailytable.app:daily-table-customer.apk" "in.dailytable.rider:daily-table-rider.apk"; do
  pkg=${p%%:*}; apk=${p##*:}
  adb install -r -g "apks/$apk" > "out/$pkg-install.txt" 2>&1
  adb logcat -c
  adb shell monkey -p "$pkg" -c android.intent.category.LAUNCHER 1 > /dev/null 2>&1
  sleep 30
  echo "pid=$(adb shell pidof $pkg)" > "out/$pkg-status.txt"
  adb shell dumpsys activity activities | grep -i "ResumedActivity" >> "out/$pkg-status.txt"
  adb logcat -d -v time > "out/$pkg-logcat-full.txt"
  grep -E "AndroidRuntime|FATAL|ReactNativeJS|ReactNative|SoLoader|Expo|expo|Exception|Error" "out/$pkg-logcat-full.txt" | tail -400 > "out/$pkg-logcat.txt"
  adb exec-out screencap -p > "out/$pkg-screen.png"
done
ls -la out
