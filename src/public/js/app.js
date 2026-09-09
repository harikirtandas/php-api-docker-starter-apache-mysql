// Cliente demo. Todo pasa por fetch + async/await y actualiza el DOM sin
// recargar la pagina. Reemplazable entero en un proyecto real.

const $ = (sel) => document.querySelector(sel);

// --- estado de la API (endpoints publicos) ---
async function chequearEstado() {
  try {
    const ping = await API.get('/ping');
    $('#ping').textContent = ping.pong ? `ok (${ping.hora})` : 'raro';
  } catch (e) {
    $('#ping').textContent = 'sin respuesta';
  }
  try {
    const db = await API.get('/health/db');
    $('#db').textContent = db.db;
  } catch (e) {
    $('#db').textContent = e.message;
  }
}

// --- login / logout ---
$('#login-form').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const f = ev.target;
  $('#login-msg').textContent = '';
  try {
    const r = await API.post('/auth/login', {
      email: f.email.value,
      password: f.password.value,
    });
    API.setToken(r.token);
    mostrarSesion(r.usuario);
    await cargarNotas();
  } catch (e) {
    $('#login-msg').textContent = e.message;
  }
});

$('#logout').addEventListener('click', async () => {
  try { await API.post('/auth/logout'); } catch (_) {}
  API.setToken(null);
  $('#notas-card').classList.add('oculto');
  $('#login-card').classList.remove('oculto');
});

function mostrarSesion(usuario) {
  $('#login-card').classList.add('oculto');
  $('#notas-card').classList.remove('oculto');
  $('#notas-card h2').firstChild.textContent =
    `Notas de ${usuario.nombre} `;
}

// --- CRUD de notas ---
async function cargarNotas() {
  const r = await API.get('/notas');
  render(r.datos);
}

function render(notas) {
  const ul = $('#notas');
  ul.innerHTML = '';
  if (notas.length === 0) {
    ul.innerHTML = '<li class="vacio">Sin notas todavía.</li>';
    return;
  }
  for (const n of notas) {
    const li = document.createElement('li');
    li.innerHTML = `<strong></strong><span></span><button class="link">borrar</button>`;
    li.querySelector('strong').textContent = n.titulo;
    li.querySelector('span').textContent = n.cuerpo;
    li.querySelector('button').addEventListener('click', async () => {
      await API.del(`/notas/${n.id}`);
      await cargarNotas();
    });
    ul.appendChild(li);
  }
}

$('#nota-form').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const f = ev.target;
  try {
    await API.post('/notas', { titulo: f.titulo.value, cuerpo: f.cuerpo.value });
    f.reset();
    await cargarNotas();
  } catch (e) {
    alert(e.errores ? JSON.stringify(e.errores) : e.message);
  }
});

// --- arranque ---
chequearEstado();
if (API.hasToken()) {
  API.get('/auth/me')
    .then((r) => { mostrarSesion(r.usuario); return cargarNotas(); })
    .catch(() => API.setToken(null));
}
