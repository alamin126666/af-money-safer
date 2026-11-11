<section class="card">
  <form id="q" class="filters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    <input class="txt" name="id" placeholder="Tx id or order id" style="flex:1;min-width:180px">
    <select name="status"><option value="">Any status</option><option>pending</option><option>processing</option><option>successful</option><option>failed</option><option>cancelled</option></select>
    <select name="type"><option value="">Any type</option><option>deposit</option><option>withdraw</option></select>
    <button class="btn btn-primary">Filter</button>
  </form>
  <div id="res"><div class="empty">…</div></div>
</section>
