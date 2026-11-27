import React from 'react';
import { Image, View } from 'react-native';
import Svg, { Circle, ClipPath, Defs, Ellipse, G, Path, Rect } from 'react-native-svg';

/**
 * Dish shown from the top. With a photo: a round, top-down photo in a white rim.
 * Without one: the illustrated banana-leaf thali from the design (rice, sambar, rasam, poriyal, curd).
 * `mound` = the grain (rice / millet), `bowl` = the main curry.
 */
export default function Plate({ uri, size = 72, mound = '#FBF8EE', bowl = '#D9822B', label }) {
  if (uri) {
    return (
      <View accessibilityRole="image" accessibilityLabel={label} style={{ width: size, height: size, borderRadius: size / 2, padding: size * 0.03, backgroundColor: '#FFFFFF', shadowColor: '#12302A', shadowOpacity: 0.12, shadowRadius: 12, shadowOffset: { width: 0, height: 6 }, elevation: 3 }}>
        <Image source={{ uri }} style={{ width: '100%', height: '100%', borderRadius: size }} accessibilityIgnoresInvertColors />
      </View>
    );
  }
  const id = 'p' + size;
  return (
    <Svg width={size} height={size} viewBox="0 0 100 100" accessibilityRole="image" accessibilityLabel={label} accessibilityElementsHidden={!label} importantForAccessibility={label ? 'yes' : 'no-hide-descendants'}>
      <G transform="translate(50 50) scale(1.45) translate(-50 -50)">
        <Defs><ClipPath id={id}><Circle cx="50" cy="50" r="34" /></ClipPath></Defs>
        <G clipPath={`url(#${id})`}>
          <Rect width="100" height="100" fill="#FFFDF7" />
          <Path d="M12 72C20 30 60 12 92 20 86 66 52 88 12 72Z" fill="#4E9F5D" />
          <Path d="M16 68C40 46 62 32 88 24" stroke="#3B7F48" strokeWidth=".8" fill="none" />
          <Circle cx="62" cy="43" r="12.5" fill={mound} />
          <G fill="#E6DEC6">
            <Ellipse cx="58" cy="39" rx="1.6" ry=".6" /><Ellipse cx="64" cy="37" rx="1.6" ry=".6" transform="rotate(30 64 37)" />
            <Ellipse cx="66" cy="46" rx="1.6" ry=".6" /><Ellipse cx="60" cy="48" rx="1.6" ry=".6" transform="rotate(-25 60 48)" /><Ellipse cx="68" cy="41" rx="1.4" ry=".6" />
          </G>
          <Circle cx="73" cy="59" r="6" fill="#F3DFA2" stroke="#E2C47A" strokeWidth=".6" />
          <Circle cx="59.5" cy="67" r="7.6" fill="#D5DAD6" /><Circle cx="59.5" cy="67" r="6.1" fill={bowl} />
          <Circle cx="58" cy="65.5" r="1.2" fill="#7FB069" /><Circle cx="61.2" cy="68.4" r="1" fill="#F2E3B3" />
          <Circle cx="45" cy="69" r="6.6" fill="#D5DAD6" /><Circle cx="45" cy="69" r="5.2" fill="#B5442A" /><Circle cx="44" cy="67.6" r=".8" fill="#5DB37E" />
          <G fill="#6DBE6A">
            <Ellipse cx="29" cy="55" rx="2.4" ry="1.3" /><Ellipse cx="32.5" cy="58.5" rx="2.2" ry="1.2" transform="rotate(40 32.5 58.5)" />
            <Ellipse cx="27" cy="60" rx="2" ry="1.1" /><Ellipse cx="33" cy="53" rx="2" ry="1.1" transform="rotate(-30 33 53)" />
          </G>
          <G fill="#F7F0D8"><Circle cx="30.5" cy="57" r=".8" /><Circle cx="28.5" cy="57.8" r=".7" /></G>
          <Circle cx="34.5" cy="42.5" r="7.2" fill="#D5DAD6" /><Circle cx="34.5" cy="42.5" r="5.8" fill="#C9B458" />
          <Circle cx="33" cy="41.5" r="1" fill="#7FB069" /><Circle cx="36" cy="43.8" r=".9" fill="#E07A4F" />
          <Circle cx="45" cy="31" r="6.2" fill="#D5DAD6" /><Circle cx="45" cy="31" r="4.8" fill="#FFFFFF" />
          <Circle cx="50" cy="54.5" r="2.4" fill="#C4562A" />
        </G>
        <Circle cx="50" cy="50" r="34" fill="none" stroke="#FFFFFF" strokeWidth="1.6" />
      </G>
    </Svg>
  );
}

/** Plate colours by dish: millet dishes get a millet mound, non-veg a darker curry. */
export function plateColors(dish) {
  const n = (dish?.name || '').toLowerCase();
  return {
    mound: n.includes('millet') || n.includes('pongal') ? '#E3D3A8' : n.includes('lemon') ? '#F2DE7A' : '#FBF8EE',
    bowl: dish?.diet === 'nonveg' ? '#8C3B1F' : dish?.diet === 'egg' ? '#E0A23A' : '#D9822B',
  };
}
