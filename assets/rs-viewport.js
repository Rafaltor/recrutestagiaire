(function () {
  /* iOS Safari : modifier initial-scale relance layout → innerWidth/visualViewport bougent →
     resize en rafale = zoom/dézoom infini. On fige la largeur après 1ère mesure ; pas d’écoute
     visualViewport/resize sur iOS sauf changement d’orientation. */
  var isIOS =
    /iP(ad|hone|od)/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  var lastScaleApplied = null;

  function rsVpStripDupes() {
    var keep = document.getElementById('rs-viewport-meta');
    if (!keep) return;
    document.querySelectorAll('meta[name="viewport"]').forEach(function (meta) {
      if (meta !== keep) meta.parentNode.removeChild(meta);
    });
  }

  function rsVpReadWidth() {
    if (isIOS && window._rsVpIosLockW != null) {
      return window._rsVpIosLockW;
    }
    var vw = 0;
    if (!isIOS && window.visualViewport && window.visualViewport.width) {
      vw = window.visualViewport.width;
    }
    if (!vw) vw = window.innerWidth || document.documentElement.clientWidth || 0;
    if (!vw && typeof screen !== 'undefined') vw = screen.width || 0;
    if (isIOS && vw > 0) {
      window._rsVpIosLockW = vw;
    }
    return vw;
  }

  function rsVpApply() {
    rsVpStripDupes();
    var m = document.getElementById('rs-viewport-meta');
    if (!m) return;
    var lw = parseInt(m.getAttribute('data-layout-w'), 10) || 1180;
    var vw = rsVpReadWidth();
    if (vw <= 0) return;
    if (vw >= lw) {
      if (lastScaleApplied === 1) return;
      lastScaleApplied = 1;
      m.setAttribute(
        'content',
        'width=' + lw + ', initial-scale=1, maximum-scale=3, user-scalable=yes, viewport-fit=cover'
      );
      return;
    }
    var s = Math.max(0.1, Math.min(1, vw / lw));
    s = Math.round(s * 10000) / 10000;
    if (lastScaleApplied !== null && Math.abs(s - lastScaleApplied) < 0.02) {
      return;
    }
    lastScaleApplied = s;
    m.setAttribute(
      'content',
      'width=' +
        lw +
        ', initial-scale=' +
        s +
        ', minimum-scale=0.1, maximum-scale=3, user-scalable=yes, viewport-fit=cover'
    );
  }

  rsVpApply();
  if (!isIOS) {
    requestAnimationFrame(rsVpApply);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      rsVpStripDupes();
      rsVpApply();
      if (!isIOS) {
        requestAnimationFrame(rsVpApply);
      }
    });
  }

  window.addEventListener('orientationchange', function () {
    window._rsVpIosLockW = null;
    lastScaleApplied = null;
    setTimeout(function () {
      rsVpStripDupes();
      rsVpApply();
    }, 350);
  });

  if (!isIOS) {
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', function () {
        clearTimeout(window._rsVvT);
        window._rsVvT = setTimeout(rsVpApply, 120);
      });
    }
    var _rsT;
    window.addEventListener('resize', function () {
      clearTimeout(_rsT);
      _rsT = setTimeout(rsVpApply, 150);
    });
  }
})();
