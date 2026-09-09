// Wrapper minimo de fetch para la API. Agrega el header Authorization si hay
// token guardado, parsea JSON y tira un Error con el mensaje del backend
// cuando la respuesta es >= 400.
const API = (() => {
  const BASE = '/api';
  let token = localStorage.getItem('token') || null;

  function setToken(t) {
    token = t;
    if (t) localStorage.setItem('token', t);
    else localStorage.removeItem('token');
  }

  async function request(metodo, ruta, body) {
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(BASE + ruta, {
      method: metodo,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (res.status === 204) return null;

    const data = await res.json().catch(() => null);

    if (!res.ok) {
      const msg = data?.error?.mensaje || `HTTP ${res.status}`;
      const err = new Error(msg);
      err.status = res.status;
      err.errores = data?.error?.errores || null;
      throw err;
    }
    return data;
  }

  return {
    get: (r) => request('GET', r),
    post: (r, b) => request('POST', r, b),
    put: (r, b) => request('PUT', r, b),
    del: (r) => request('DELETE', r),
    setToken,
    hasToken: () => !!token,
  };
})();
