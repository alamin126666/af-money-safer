AF.ready(function () {
  var b = AF.$('#printBtn');
  if (b) { b.addEventListener('click', function () { window.print(); }); }
});
