$(document).ready(function () {
  $("table.racers").DataTable({
    responsive: true,
    paging: false,
    dom: "ftip",
    language: {
      search: "Hľadať:",
      lengthMenu: "Zobraziť _MENU_ záznamov",
      zeroRecords: "Nenašli sa žiadne záznamy",
      info: "Zobrazené _START_ až _END_ z _TOTAL_ záznamov",
      infoEmpty: "Žiadne dostupné záznamy",
      infoFiltered: "(filtrované z _MAX_ celkových záznamov)",
      paginate: {
        first: "Prvá",
        last: "Posledná",
        next: "Ďalej",
        previous: "Späť"
      },
      emptyTable: "Tabuľka je prázdna",
      loadingRecords: "Načítavam...",
      processing: "Spracovávam...",
      searchPlaceholder: "Zadajte výraz...",
      select: {
        rows: {
          _: "Vybratých _%d_ riadkov",
          0: "Kliknite na riadok pre výber",
          1: "Vybraný 1 riadok"
        }
      },
    },
  });
});
