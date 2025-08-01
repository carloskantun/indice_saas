
<!-- Scripts requeridos para Bootstrap y funcionamiento de modales -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Función para cargar contenido en los modales
document.addEventListener("DOMContentLoaded", function () {
  const modales = [
    { id: "modalAgregarUsuario", url: "usuarios.php?modal=1", cont: "contenidoUsuario" },
    { id: "modalAgregarAlojamiento", url: "alojamientos.php?modal=1", cont: "contenidoAlojamiento" },
    { id: "modalIngresarOrden", url: "ordenes_mantenimiento.php?modal=1", cont: "contenidoOrden" }
  ];

  modales.forEach(function(modalInfo) {
    let modal = document.getElementById(modalInfo.id);
    if (modal) {
      modal.addEventListener("show.bs.modal", function () {
        fetch(modalInfo.url)
          .then(response => {
            if (!response.ok) throw new Error("No se pudo cargar el contenido.");
            return response.text();
          })
          .then(html => {
            document.getElementById(modalInfo.cont).innerHTML = html;
          })
          .catch(() => {
            document.getElementById(modalInfo.cont).innerHTML = "<p class='text-danger'>Error al cargar contenido.</p>";
          });
      });

      modal.addEventListener("hidden.bs.modal", function () {
        document.getElementById(modalInfo.cont).innerHTML = "<p class='text-center'>Cargando...</p>";
      });
    }
  });
});
</script>
