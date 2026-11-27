#!/bin/bash
# $1 = package, $2 = apk path
pkg=$1; apk=$2; mkdir -p out
adb install -r -g "$apk" > "out/install.txt" 2>&1
adb logcat -c
adb shell monkey -p "$pkg" -c android.intent.category.LAUNCHER 1 > /dev/null 2>&1
sleep 40
echo "pid=$(adb shell pidof $pkg)" > "out/status.txt"
adb shell dumpsys activity activities | grep -i "ResumedActivity" >> "out/status.txt"
adb exec-out screencap -p > "out/screen.png"
adb logcat -d -v time > "out/logcat-full.txt"
grep -E "AndroidRuntime|FATAL|ReactNativeJS|ReactNative|SoLoader|Exception" "out/logcat-full.txt" | tail -300 > "out/logcat.txt"
ls -la out
