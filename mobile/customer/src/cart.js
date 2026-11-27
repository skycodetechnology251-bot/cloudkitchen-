import React, { createContext, useContext, useMemo, useState } from 'react';

const Ctx = createContext(null);
export const useCart = () => useContext(Ctx);

/** Basket for one kitchen and one day. Prices shown here are for display; the server re-prices at checkout. */
export function CartProvider({ children }) {
  const [kitchenId, setKitchenId] = useState(null);
  const [addressId, setAddressId] = useState(null);
  const [lines, setLines] = useState({}); // daily_menu_id -> { item, qty }

  const value = useMemo(() => {
    const count = Object.values(lines).reduce((a, l) => a + l.qty, 0);
    const subtotal = Object.values(lines).reduce((a, l) => a + l.qty * l.item.price_paise, 0);
    return {
      kitchenId, setKitchenId, addressId, setAddressId, lines, count, subtotal,
      qty: (id) => lines[id]?.qty || 0,
      add: (item) => setLines((s) => ({ ...s, [item.daily_menu_id]: { item, qty: (s[item.daily_menu_id]?.qty || 0) + 1 } })),
      remove: (id) => setLines((s) => { const n = { ...s }; if (!n[id]) return s; n[id] = { ...n[id], qty: n[id].qty - 1 }; if (n[id].qty <= 0) delete n[id]; return n; }),
      clear: () => setLines({}),
      payload: () => Object.entries(lines).map(([id, l]) => ({ daily_menu_id: Number(id), quantity: l.qty })),
    };
  }, [kitchenId, addressId, lines]);

  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}
