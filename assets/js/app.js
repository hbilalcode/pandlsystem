// Confirm before delete / destructive actions
document.addEventListener('click', function(e){
  const a = e.target.closest('[data-confirm]');
  if(!a) return;
  if(!confirm(a.getAttribute('data-confirm') || 'Are you sure?')) e.preventDefault();
});

/**
 * Print helper. Uses CSS @media print rules to hide chrome and
 * print only the .report element on the page.
 */
function printReport(){ window.print(); }
