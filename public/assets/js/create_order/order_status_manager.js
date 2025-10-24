window.addEventListener("beforeunload", async (e) => {
  const hasCustomer = !!document.getElementById("customer_id")?.value;
  const hasData = document.querySelector("#order_name")?.value.trim() !== "";

  // Solo elimina si no hay cliente y no hay datos
  if (!hasCustomer && !hasData) {
    const systemId = document.getElementById("system_order_id")?.value;
    if (systemId) {
      await fetch("index.php?page=delete_temporary_order", {
        method: "POST",
        body: new URLSearchParams({ system_id: systemId }),
      });
      console.log("🧹 Orden temporal eliminada (vacía).");
    }
  }
});
