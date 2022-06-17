!(function (n) {
  'use strict';
  (n.fn.fitVids = function (e) {
    var t,
      i,
      s = { customSelector: null, ignore: null };
    return (
      document.getElementById('fit-vids-style') ||
        ((t = document.head || document.getElementsByTagName('head')[0]),
        ((i = document.createElement('div')).innerHTML =
          '<p>x</p><style id="fit-vids-style">.fluid-width-video-container{flex-grow: 1;width:100%;}.fluid-width-video-wrapper{width:100%;position:relative;padding:0;}.fluid-width-video-wrapper iframe,.fluid-width-video-wrapper object,.fluid-width-video-wrapper embed {position:absolute;top:0;left:0;width:100%;height:100%;}</style>'),
        t.appendChild(i.childNodes[1])),
      e && n.extend(s, e),
      this.each(function () {
        var e = [
            'iframe[src*="player.vimeo.com"]',
            'iframe[src*="youtube.com"]',
            'iframe[src*="youtube-nocookie.com"]',
            'iframe[src*="kickstarter.com"][src*="video.html"]',
            'object',
            'embed',
          ],
          o = (s.customSelector && e.push(s.customSelector), '.fitvidsignore'),
          e =
            (s.ignore && (o = o + ', ' + s.ignore), n(this).find(e.join(',')));
        (e = (e = e.not('object object')).not(o)).each(function () {
          var e,
            t,
            i = n(this);
          0 < i.parents(o).length ||
            ('embed' === this.tagName.toLowerCase() &&
              i.parent('object').length) ||
            i.parent('.fluid-width-video-wrapper').length ||
            (i.css('height') ||
              i.css('width') ||
              (!isNaN(i.attr('height')) && !isNaN(i.attr('width'))) ||
              (i.attr('height', 9), i.attr('width', 16)),
            (e =
              ('object' === this.tagName.toLowerCase() ||
              (i.attr('height') && !isNaN(parseInt(i.attr('height'), 10)))
                ? parseInt(i.attr('height'), 10)
                : i.height()) /
              (isNaN(parseInt(i.attr('width'), 10))
                ? i.width()
                : parseInt(i.attr('width'), 10))),
            i.attr('name') ||
              ((t = 'fitvid' + n.fn.fitVids._count),
              i.attr('name', t),
              n.fn.fitVids._count++),
            i
              .wrap(
                '<div class="fluid-width-video-container"><div class="fluid-width-video-wrapper"></div></div>'
              )
              .parent('.fluid-width-video-wrapper')
              .css('padding-top', 100 * e + '%'),
            i.removeAttr('height').removeAttr('width'));
        });
      })
    );
  }),
    (n.fn.fitVids._count = 0);
})(window.jQuery || window.Zepto),
  (function (t, i) {
    var o,
      s,
      n,
      r,
      c,
      a,
      u,
      h = i.querySelector('link[rel=next]');
    function l() {
      if (404 === this.status)
        return (
          t.removeEventListener('scroll', p),
          void t.removeEventListener('resize', m)
        );
      this.response.querySelectorAll('article.post-card').forEach(function (e) {
        o.appendChild(i.importNode(e, !0));
      });
      var e = this.response.querySelector('link[rel=next]');
      e
        ? (h.href = e.href)
        : (t.removeEventListener('scroll', p),
          t.removeEventListener('resize', m)),
        (u = i.documentElement.scrollHeight),
        (r = n = !1);
    }
    function e() {
      var e;
      r ||
        (c + a <= u - s
          ? (n = !1)
          : ((r = !0),
            ((e = new t.XMLHttpRequest()).responseType = 'document'),
            e.addEventListener('load', l),
            e.open('GET', h.href),
            e.send(null)));
    }
    function d() {
      n || t.requestAnimationFrame(e), (n = !0);
    }
    function p() {
      (c = t.scrollY), d();
    }
    function m() {
      (a = t.innerHeight), (u = i.documentElement.scrollHeight), d();
    }
    !h ||
      ((o = i.querySelector('.post-feed')) &&
        ((r = n = !(s = 300)),
        (c = t.scrollY),
        (a = t.innerHeight),
        (u = i.documentElement.scrollHeight),
        t.addEventListener('scroll', p, { passive: !0 }),
        t.addEventListener('resize', m),
        d()));
  })(window, document);
//# sourceMappingURL=casper.js.map
