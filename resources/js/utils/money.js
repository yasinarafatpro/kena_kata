const currency = import.meta.env.VITE_CURRENCY || 'USD'

export const formatMoney = (value, cur = currency) =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency: cur }).format(Number(value))

export const safeRedirect = (target) =>
  typeof target === 'string' && target.startsWith('/') && !target.startsWith('//') ? target : '/'
