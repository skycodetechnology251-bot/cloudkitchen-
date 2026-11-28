#!/bin/bash
# $1 = package, $2 = apk path
pkg=$1; apk=$2; mkdir -p out
adb install -r -g "$apk" > "out/install.txt" 2>&1
adb logcat -c
adb shell monkey -p "$pkg" -c android.intent.category.LAUNCHER 1 > /dev/null 2>&1
sleep 40
echo "pid=$(adb shell pidof $pkg)" > "out/status.txt"
adb exec-out screencap -p > "out/screen.png"
# Tap the first text box (mobile number / email), type, and screenshot with the keyboard open.
adb shell uiautomator dump /sdcard/ui.xml >/dev/null 2>&1; adb shell cat /sdcard/ui.xml > out/ui.xml
b=$(grep -o 'class="android.widget.EditText"[^>]*bounds="[^"]*"' out/ui.xml | head -1 | grep -o 'bounds="[^"]*"' | grep -o '[0-9]\+' | tr '\n' ' ')
echo "field bounds before: $b" >> out/status.txt
if [ -z "$b" ]; then
  # Screen keeps animating, so uiautomator can't read it: tap where the number box is (64% across, 74% down).
  sz=$(adb shell wm size | grep -o '[0-9]*x[0-9]*' | tail -1); W=${sz%x*}; H=${sz#*x}
  b="$((W*55/100)) $((H*72/100)) $((W*73/100)) $((H*76/100))"; echo "screen $W x $H, tapping $b" >> out/status.txt
  SKIPB="$((W*80/100)) $((H*8/100)) $((W*87/100)) $((H*10/100))"
fi
set -- $b
if [ -n "$4" ]; then
  adb shell input tap $(( ($1+$3)/2 )) $(( ($2+$4)/2 )); sleep 3
  adb shell input text 9840012345; sleep 3
  adb exec-out screencap -p > "out/screen-keyboard.png"
  adb shell dumpsys input_method | grep -i "mInputShown\|mImeWindowVis" | head -3 >> out/status.txt
  adb shell uiautomator dump /sdcard/ui2.xml >/dev/null 2>&1; adb shell cat /sdcard/ui2.xml > out/ui-keyboard.xml
  echo "field after: $(grep -o 'class="android.widget.EditText"[^>]*bounds="[^"]*"' out/ui-keyboard.xml | head -1 | grep -o 'text="[^"]*"\|bounds="[^"]*"' | tr '\n' ' ')" >> out/status.txt
  adb shell dumpsys window InputMethod | grep -i "mFrame\|touchableRegion\|mGivenContentInsets" | head -4 >> out/status.txt
  adb shell input keyevent 4; sleep 2
fi
# Skip (customer app): browse without logging in.
s=${SKIPB:-$(grep -o 'content-desc="Skip[^"]*"[^>]*bounds="[^"]*"' out/ui.xml | head -1 | grep -o 'bounds="[^"]*"' | grep -o '[0-9]\+' | tr '\n' ' ')}
set -- $s
if [ -n "$4" ]; then
  adb shell input tap $(( ($1+$3)/2 )) $(( ($2+$4)/2 )); sleep 12
  adb exec-out screencap -p > "out/screen-guest.png"
  echo "after skip pid=$(adb shell pidof $pkg)" >> out/status.txt
fi
adb shell dumpsys activity activities | grep -i "ResumedActivity" >> "out/status.txt"
adb logcat -d -v time > "out/logcat-full.txt"
grep -E "AndroidRuntime|FATAL|ReactNativeJS|SoLoader|Exception" "out/logcat-full.txt" | tail -300 > "out/logcat.txt"
ls -la out
