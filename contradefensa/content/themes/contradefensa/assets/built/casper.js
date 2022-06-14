!(function (s) {
  'use strict';
  (s.fn.fitVids = function (e) {
    var t,
      i,
      n = { customSelector: null, ignore: null };
    return (
      document.getElementById('fit-vids-style') ||
        ((t = document.head || document.getElementsByTagName('head')[0]),
        ((i = document.createElement('div')).innerHTML =
          '<p>x</p><style id="fit-vids-style">.fluid-width-video-container{flex-grow: 1;width:100%;}.fluid-width-video-wrapper{width:100%;position:relative;padding:0;}.fluid-width-video-wrapper iframe,.fluid-width-video-wrapper object,.fluid-width-video-wrapper embed {position:absolute;top:0;left:0;width:100%;height:100%;}</style>'),
        t.appendChild(i.childNodes[1])),
      e && s.extend(n, e),
      this.each(function () {
        var e = [
            'iframe[src*="player.vimeo.com"]',
            'iframe[src*="youtube.com"]',
            'iframe[src*="youtube-nocookie.com"]',
            'iframe[src*="kickstarter.com"][src*="video.html"]',
            'object',
            'embed',
          ],
          r = (n.customSelector && e.push(n.customSelector), '.fitvidsignore'),
          e =
            (n.ignore && (r = r + ', ' + n.ignore), s(this).find(e.join(',')));
        (e = (e = e.not('object object')).not(r)).each(function () {
          var e,
            t,
            i = s(this);
          0 < i.parents(r).length ||
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
              ((t = 'fitvid' + s.fn.fitVids._count),
              i.attr('name', t),
              s.fn.fitVids._count++),
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
    (s.fn.fitVids._count = 0);
})(window.jQuery || window.Zepto),
  !(function (t, i) {
    var r,
      n,
      s,
      o,
      c,
      a,
      d,
      l = i.querySelector('link[rel=next]');
    function u() {
      if (404 === this.status)
        return (
          t.removeEventListener('scroll', p),
          void t.removeEventListener('resize', m)
        );
      this.response.querySelectorAll('article.post-card').forEach(function (e) {
        r.appendChild(i.importNode(e, !0));
      });
      var e = this.response.querySelector('link[rel=next]');
      e
        ? (l.href = e.href)
        : (t.removeEventListener('scroll', p),
          t.removeEventListener('resize', m)),
        (d = i.documentElement.scrollHeight),
        (o = s = !1);
    }
    function e() {
      var e;
      o ||
        (c + a <= d - n
          ? (s = !1)
          : ((o = !0),
            ((e = new t.XMLHttpRequest()).responseType = 'document'),
            e.addEventListener('load', u),
            e.open('GET', l.href),
            e.send(null)));
    }
    function h() {
      s || t.requestAnimationFrame(e), (s = !0);
    }
    function p() {
      (c = t.scrollY), h();
    }
    function m() {
      (a = t.innerHeight), (d = i.documentElement.scrollHeight), h();
    }
    !l ||
      ((r = i.querySelector('.post-feed')) &&
        ((o = s = !(n = 300)),
        (c = t.scrollY),
        (a = t.innerHeight),
        (d = i.documentElement.scrollHeight),
        t.addEventListener('scroll', p, { passive: !0 }),
        t.addEventListener('resize', m),
        h()));
  })(window, document);
//# sourceMappingURL=casper.js.map
