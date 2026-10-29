document.addEventListener('DOMContentLoaded', () => {
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];

  $$('[data-toggle-password]').forEach(btn => btn.addEventListener('click', () => {
    const input = $(btn.dataset.togglePassword); if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? 'Show' : 'Hide';
  }));

  $$('[data-sidebar-open]').forEach(btn => btn.addEventListener('click', () => $('#sidebar')?.classList.add('open')));
  $$('[data-sidebar-close]').forEach(btn => btn.addEventListener('click', () => $('#sidebar')?.classList.remove('open')));
  $$('.sidebar-scroll a').forEach(link => link.addEventListener('click', () => { if (window.innerWidth <= 820) $('#sidebar')?.classList.remove('open'); }));

  const publicMenu = $('[data-public-menu]');
  const publicToggle = $('[data-public-menu-toggle]');
  publicToggle?.addEventListener('click', () => {
    const open = publicMenu?.classList.toggle('is-open');
    publicToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  $$('.top-nav a').forEach(link => link.addEventListener('click', () => {
    publicMenu?.classList.remove('is-open'); publicToggle?.setAttribute('aria-expanded', 'false');
  }));

  const publicAppearanceToggle = $('[data-public-appearance-toggle]');
  const publicAppearance = $('[data-public-appearance-popover]');
  const publicAppearanceClose = $('[data-public-appearance-close]');
  const publicThemeChoices = $$('[data-public-theme]');
  const publicFontChoices = $$('[data-public-font]');
  const publicStorage = { theme:'cc_public_theme', font:'cc_public_font' };
  const setPublicTheme = theme => {
    document.body.classList.toggle('public-light', theme === 'light');
    localStorage.setItem(publicStorage.theme, theme);
    publicThemeChoices.forEach(btn => btn.classList.toggle('selected', btn.dataset.publicTheme === theme));
  };
  const setPublicFont = size => {
    document.body.classList.toggle('public-font-large', size === 'large');
    localStorage.setItem(publicStorage.font, size);
    publicFontChoices.forEach(btn => btn.classList.toggle('selected', btn.dataset.publicFont === size));
  };
  if (publicAppearanceToggle && publicAppearance) {
    const savedTheme = localStorage.getItem(publicStorage.theme) || 'dark';
    const savedFont = localStorage.getItem(publicStorage.font) || 'medium';
    setPublicTheme(savedTheme); setPublicFont(savedFont);
    publicAppearanceToggle.addEventListener('click', () => {
      const open = !publicAppearance.hasAttribute('hidden');
      if (open) { publicAppearance.setAttribute('hidden',''); } else { publicAppearance.removeAttribute('hidden'); }
      publicAppearanceToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
    });
    publicAppearanceClose?.addEventListener('click', () => { publicAppearance.setAttribute('hidden',''); publicAppearanceToggle.setAttribute('aria-expanded','false'); });
    publicThemeChoices.forEach(btn => btn.addEventListener('click', () => setPublicTheme(btn.dataset.publicTheme || 'dark')));
    publicFontChoices.forEach(btn => btn.addEventListener('click', () => setPublicFont(btn.dataset.publicFont || 'medium')));
    document.addEventListener('click', e => { if(!publicAppearance.contains(e.target) && e.target !== publicAppearanceToggle && !publicAppearanceToggle.contains(e.target)) { publicAppearance.setAttribute('hidden',''); publicAppearanceToggle.setAttribute('aria-expanded','false'); } });
  }

  const appearanceToggles = $$('[data-appearance-toggle]');
  const appearancePopover = $('[data-appearance-popover]');
  const appearanceClose = $('[data-appearance-close]');
  appearanceToggles.forEach(btn => btn.addEventListener('click', () => {
    if(!appearancePopover) return;
    const open = !appearancePopover.hasAttribute('hidden');
    if(open) appearancePopover.setAttribute('hidden',''); else appearancePopover.removeAttribute('hidden');
    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
  }));
  appearanceClose?.addEventListener('click', () => { appearancePopover?.setAttribute('hidden',''); appearanceToggles.forEach(b => b.setAttribute('aria-expanded','false')); });
  document.addEventListener('click', e => { if(appearancePopover && !appearancePopover.contains(e.target) && !appearanceToggles.some(b => b.contains(e.target))) { appearancePopover.setAttribute('hidden',''); appearanceToggles.forEach(b => b.setAttribute('aria-expanded','false')); } });


  const progress = $('.cc-scroll-progress');
  const updateProgress = () => {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    if (progress) progress.style.width = `${max > 0 ? Math.min(100, Math.max(0, window.scrollY / max * 100)) : 0}%`;
  };
  updateProgress(); window.addEventListener('scroll', updateProgress, { passive: true });

  const header = $('[data-header]');
  const syncHeader = () => header?.classList.toggle('scrolled', window.scrollY > 18);
  syncHeader(); window.addEventListener('scroll', syncHeader, { passive: true });

  const revealTargets = [...new Set($$('[data-reveal], #statement .cc-statement-grid > *, .cc-step, .cc-feature, .cc-intel-frame, .cc-note, .cc-cta-panel, .cc-sitemap-grid > div, .page-head, .panel, .stat-card, .feature, .tip-large, .saved-card, .report-summary > div'))];
  revealTargets.forEach((el, i) => {
    el.dataset.reveal = '';
    if (!el.dataset.revealDelay && !el.style.getPropertyValue('--reveal-delay')) el.style.setProperty('--reveal-delay', `${Math.min(i * 25, 180)}ms`);
  });
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), { threshold: .12, rootMargin: '0px 0px -7% 0px' });
    revealTargets.forEach(el => observer.observe(el));
  } else revealTargets.forEach(el => el.classList.add('is-visible'));

  const heroStage = $('.cc-hero-stage');
  if (heroStage && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.addEventListener('scroll', () => heroStage.style.transform = `translate3d(0,${Math.min(window.scrollY * .055, 36)}px,0)`, { passive: true });
  }

  const csrf = $('meta[name="csrf-token"]')?.content;
  const desc = $('#description'); const cat = $('#category');
  if (desc && window.TRANSACTION_AI_URL) {
    let timer;
    const go = async () => {
      const text = desc.value.trim(); const type = $('input[name="type"]:checked')?.value || 'expense';
      const box = $('#suggestion-text'); const conf = $('#suggestion-confidence');
      if (!text) { if (box) box.textContent='Type a description to get a smart category suggestion.'; if (conf) conf.textContent=''; return; }
      const fd = new FormData(); fd.append('csrf_token', window.TRANSACTION_CSRF || csrf || ''); fd.append('description', text); fd.append('type', type);
      try { const r = await fetch(window.TRANSACTION_AI_URL, { method:'POST', body:fd }); const j = await r.json();
        if (j.ok && j.suggestion) { if (box) box.textContent=j.suggestion.name; if (conf) conf.textContent=Math.round(j.suggestion.confidence*100)+'% suggestion confidence'; if (cat) cat.value=String(j.suggestion.category_id); }
        else { if (box) box.textContent='No automatic match. Choose manually.'; if (conf) conf.textContent=''; }
      } catch { if (box) box.textContent='Smart suggestion unavailable. Choose manually.'; if (conf) conf.textContent=''; }
    };
    desc.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(go, 350); });
    $$('input[name="type"]').forEach(x => x.addEventListener('change', go));
  }

  function drawCanvasChart(id, data, keys) {
    const canvas = document.getElementById(id); if (!canvas || !data?.labels) return;
    const parent = canvas.parentElement;
    const rect = parent?.getBoundingClientRect();
    const width = Math.max(320, Math.floor(rect?.width || parent?.clientWidth || 700));
    const height = Math.max(220, Math.floor(rect?.height || parent?.clientHeight || 260));
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.floor(width*dpr); canvas.height = Math.floor(height*dpr); canvas.style.width='100%'; canvas.style.height=`${height}px`;
    const ctx = canvas.getContext('2d'); if (!ctx) return;
    ctx.setTransform(dpr,0,0,dpr,0,0); ctx.clearRect(0,0,width,height);
    const pad={l:46,r:16,t:18,b:30}, iw=width-pad.l-pad.r, ih=height-pad.t-pad.b;
    const all=[]; keys.forEach(key => (data[key]||[]).forEach(v => all.push(Number(v)||0))); const max=Math.max(1,...all)*1.12;
    ctx.strokeStyle='rgba(214,232,216,.10)'; ctx.lineWidth=1;
    for(let i=0;i<5;i++){
      const y=pad.t+ih*i/4;ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(width-pad.r,y);ctx.stroke();
      ctx.fillStyle='rgba(151,168,157,.78)';ctx.font='10px Inter,Arial,sans-serif';ctx.fillText(Math.round(max*(1-i/4)).toString(),8,y+3);
    }
    const palette=['#8fd6a9','#8ec7de'];
    keys.forEach((key,si)=>{const vals=(data[key]||[]).map(Number); if(!vals.length)return; ctx.beginPath(); vals.forEach((v,i)=>{const x=pad.l+(vals.length===1?iw/2:i/(vals.length-1)*iw);const y=pad.t+ih-(Math.max(0,v)/max)*ih;i?ctx.lineTo(x,y):ctx.moveTo(x,y);}); ctx.strokeStyle=palette[si%palette.length];ctx.lineWidth=2.5;ctx.lineCap='round';ctx.lineJoin='round';ctx.stroke();});
    data.labels.forEach((label,i)=>{if(data.labels.length>12&&i%Math.ceil(data.labels.length/10)!==0&&i!==data.labels.length-1)return;const x=pad.l+(data.labels.length===1?iw/2:i/(data.labels.length-1)*iw);ctx.fillStyle='rgba(151,168,157,.78)';ctx.font='9px Inter,Arial,sans-serif';ctx.textAlign='center';ctx.fillText(String(label),x,height-8);});
  }

  function drawSvgChart(id, data) {
    const svg=document.getElementById(id); if(!svg || !data?.labels?.length) return;
    const labels=data.labels.map(String), vals=(data.values||[]).map(v=>Number(v)||0);
    const w=900,h=300,pad={l:48,r:20,t:22,b:34},iw=w-pad.l-pad.r,ih=h-pad.t-pad.b;
    const max=Math.max(1,...vals)*1.12;
    const esc=s=>String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    const pts=vals.map((v,i)=>{const x=pad.l+(vals.length===1?iw/2:i/(Math.max(1,vals.length-1))*iw);const y=pad.t+ih-(Math.max(0,v)/max)*ih;return [x,y];});
    const path=pts.map((p,i)=>(i?'L':'M')+p[0].toFixed(1)+' '+p[1].toFixed(1)).join(' ');
    const area=pts.length?`${path} L ${pts.at(-1)[0].toFixed(1)} ${h-pad.b} L ${pts[0][0].toFixed(1)} ${h-pad.b} Z`:'';
    let grid=''; for(let i=0;i<5;i++){const y=pad.t+ih*i/4;grid+=`<line class="grid" x1="${pad.l}" y1="${y}" x2="${w-pad.r}" y2="${y}"/>`+`<text x="10" y="${y+4}">${Math.round(max*(1-i/4)).toLocaleString()}</text>`;}
    let labelsSvg=''; labels.forEach((lab,i)=>{if(labels.length>12&&i%Math.ceil(labels.length/10)!==0&&i!==labels.length-1)return;const x=pad.l+(labels.length===1?iw/2:i/Math.max(1,labels.length-1)*iw);labelsSvg+=`<text x="${x}" y="${h-9}" text-anchor="middle">${esc(lab)}</text>`;});
    let dots=''; pts.forEach(p=>{dots+=`<circle class="dot" cx="${p[0]}" cy="${p[1]}" r="3.6"/>`;});
    svg.innerHTML=`<defs><linearGradient id="spendingArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#8fd6a9" stop-opacity=".18"/><stop offset="100%" stop-color="#8fd6a9" stop-opacity="0"/></linearGradient></defs>${grid}<path class="area" d="${area}"/><path class="line" d="${path}"/>${dots}${labelsSvg}`;
  }

  const renderCharts=()=>{
    try {
      if(window.CAMPUS_CHART) {
        drawSvgChart('spendingChart',window.CAMPUS_CHART);
        const empty=$('#spendingChartEmpty');
        if(empty) empty.hidden=!!window.CAMPUS_CHART.hasData;
      }
      if(window.REPORT_DATA) drawCanvasChart('reportChart',window.REPORT_DATA,['income','expense']);
      if(window.ADMIN_DATA) drawCanvasChart('adminChart',window.ADMIN_DATA,['income','expense']);
    } catch (err) { console.error('Campus Coin chart render failed',err); }
  };
  const scheduleCharts=()=>requestAnimationFrame(()=>requestAnimationFrame(renderCharts));
  scheduleCharts(); window.addEventListener('load',scheduleCharts); window.addEventListener('resize',scheduleCharts,{passive:true});
  if(document.fonts?.ready) document.fonts.ready.then(scheduleCharts).catch(()=>{});
  if('ResizeObserver' in window){const ro=new ResizeObserver(scheduleCharts);['reportChart','adminChart','spendingChart'].forEach(id=>{const c=document.getElementById(id);if(c?.parentElement)ro.observe(c.parentElement);});}
});
