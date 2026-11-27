import { createContext, useContext } from 'react';

export const RiderCtx = createContext(null);
export const useRider = () => useContext(RiderCtx);
