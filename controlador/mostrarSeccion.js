function mostrarSeccion(id) {
  if (id === 'volver') {
    // Redirigir a la página principal o la que quieras
    window.location.href = '../vista/admin.html';
    return; // Salir para que no haga nada más
  }
  // Para otros botones, mostrar la sección correspondiente
  document.querySelectorAll('section').forEach(sec => sec.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}
