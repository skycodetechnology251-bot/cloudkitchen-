/** Rider palette from the designs. */
export const R = { bg: '#F1F5FA', ink: '#13324F', muted: '#3E5468', blue: '#1F5FA8', blueSoft: '#D6E6F7', green: '#1E8A5A', amber: '#E8A317', amberBg: '#FFF6E0', badge: '#F2CF4A', badgeInk: '#3E2E05', bad: '#FBEAE2', badInk: '#8A3A17', note: '#FFF3CF', noteInk: '#6B4A06' };
export const BLOBS = ['rgba(120,170,235,0.65)', 'rgba(130,215,180,0.45)', 'rgba(244,196,96,0.35)'];

/** Straight-line distance in km (for "4 orders · 3.8 km"). */
export function km(points) {
  let d = 0;
  for (let i = 1; i < points.length; i++) {
    const [a, b] = [points[i - 1], points[i]];
    if (a?.lat == null || b?.lat == null) continue;
    const r = Math.PI / 180, x = (b.lng - a.lng) * r * Math.cos(((a.lat + b.lat) / 2) * r), y = (b.lat - a.lat) * r;
    d += Math.sqrt(x * x + y * y) * 6371;
  }
  return d;
}
export const pts = (b) => [{ lat: +b.kitchen?.lat, lng: +b.kitchen?.lng }, ...b.stops.map((s) => ({ lat: s.address?.lat != null ? +s.address.lat : null, lng: s.address?.lng != null ? +s.address.lng : null }))];
