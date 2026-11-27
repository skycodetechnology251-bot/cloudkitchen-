import React from 'react';
import Svg, { Circle, Path, Rect } from 'react-native-svg';
import { C } from './theme';

/** Line icons used across the designs (24×24, 2px stroke). */
const P = {
  back: <Path d="M19 12H5M11 6l-6 6 6 6" />,
  bell: <><Path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8" /><Path d="M10 20a2 2 0 0 0 4 0" /></>,
  heart: <Path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" />,
  home: <Path d="M3 11 12 4l9 7v9h-6v-6H9v6H3z" />,
  phone: <Path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" />,
  lock: <><Rect x="5" y="11" width="14" height="10" rx="2" /><Path d="M8 11V7a4 4 0 0 1 8 0v4" /></>,
  search: <><Circle cx="11" cy="11" r="7" /><Path d="m20 20-4-4" /></>,
  filter: <Path d="M4 6h16M7 12h10M10 18h4" />,
  wallet: <><Rect x="3" y="6" width="18" height="13" rx="3" /><Path d="M16 12h2M3 10h18" /></>,
  check: <Path d="m5 12 5 5 9-10" />,
  pin: <><Path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z" /><Circle cx="12" cy="10" r="2.5" /></>,
  target: <><Circle cx="12" cy="12" r="7" /><Circle cx="12" cy="12" r="2.5" /><Path d="M12 2v3M12 19v3M2 12h3M19 12h3" /></>,
  plus: <Path d="M12 5v14M5 12h14" />,
  minus: <Path d="M5 12h14" />,
  chevron: <Path d="m6 9 6 6 6-6" />,
  right: <Path d="m9 6 6 6-6 6" />,
  info: <><Circle cx="12" cy="12" r="9" /><Path d="M12 11v5M12 8h.01" /></>,
  mobile: <><Rect x="6" y="2" width="12" height="20" rx="3" /><Path d="M11 18h2" /></>,
  globe: <><Circle cx="12" cy="12" r="9" /><Path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" /></>,
  bowl: <Path d="M4 11h16a8 8 0 0 1-16 0ZM12 3v4M8 5v2M16 5v2" />,
  route: <><Circle cx="6" cy="18" r="2" /><Circle cx="18" cy="6" r="2" /><Path d="M8 18h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7" /></>,
  user: <><Circle cx="12" cy="8" r="4" /><Path d="M4 21a8 8 0 0 1 16 0" /></>,
  receipt: <Path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6" />,
  backspace: <><Path d="M9 5h11a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H9l-6-7Z" /><Path d="m12 9 6 6M18 9l-6 6" /></>,
  nav: <Path d="m3 11 18-8-8 18-2-8Z" />,
};

export default function Icon({ name, size = 20, color = C.ink, width = 2, fill = 'none' }) {
  return <Svg width={size} height={size} viewBox="0 0 24 24" fill={fill} stroke={color} strokeWidth={width} strokeLinecap="round" strokeLinejoin="round">{P[name]}</Svg>;
}
