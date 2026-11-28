import { useEffect, useRef, useState } from 'react';
import { Dimensions, Keyboard, Platform } from 'react-native';

/**
 * How many pixels of `ref`'s view the on-screen keyboard covers right now.
 * Works on Android phones that resize the screen for the keyboard and on newer
 * edge-to-edge ones that don't, and on iPhone. Add it as bottom padding to a ScrollView.
 */
export function useKeyboardPad(ref, onShown) {
  const [pad, setPad] = useState(0);
  const cb = useRef(onShown); cb.current = onShown;
  useEffect(() => {
    const onShow = (e) => {
      const kbH = e?.endCoordinates?.height || 0;
      const fit = () => {
        const node = ref.current;
        if (!node?.measureInWindow) { setPad(kbH); return; }
        node.measureInWindow((x, y, w, h) => {
          const winH = Dimensions.get('window').height;
          const below = Math.max(0, winH - (y + h)); // part of the window already under this view
          setPad(Math.max(0, kbH - below));
          setTimeout(() => cb.current && cb.current(kbH), 60);
        });
      };
      setTimeout(fit, Platform.OS === 'android' ? 120 : 0);
    };
    const s = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', onShow);
    const h = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', () => setPad(0));
    return () => { s.remove(); h.remove(); };
  }, [ref]);
  return pad;
}
