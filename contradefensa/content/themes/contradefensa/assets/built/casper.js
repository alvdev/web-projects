!(function (r) {
  'use strict';
  (r.fn.fitVids = function (e) {
    var t,
      i,
      n = { customSelector: null, ignore: null };
    return (
      document.getElementById('fit-vids-style') ||
        ((t = document.head || document.getElementsByTagName('head')[0]),
        ((i = document.createElement('div')).innerHTML =
          '<p>x</p><style id="fit-vids-style">.fluid-width-video-container{flex-grow: 1;width:100%;}.fluid-width-video-wrapper{width:100%;position:relative;padding:0;}.fluid-width-video-wrapper iframe,.fluid-width-video-wrapper object,.fluid-width-video-wrapper embed {position:absolute;top:0;left:0;width:100%;height:100%;}</style>'),
        t.appendChild(i.childNodes[1])),
      e && r.extend(n, e),
      this.each(function () {
        var e = [
            'iframe[src*="player.vimeo.com"]',
            'iframe[src*="youtube.com"]',
            'iframe[src*="youtube-nocookie.com"]',
            'iframe[src*="kickstarter.com"][src*="video.html"]',
            'object',
            'embed',
          ],
          s = (n.customSelector && e.push(n.customSelector), '.fitvidsignore'),
          e =
            (n.ignore && (s = s + ', ' + n.ignore), r(this).find(e.join(',')));
        (e = (e = e.not('object object')).not(s)).each(function () {
          var e,
            t,
            i = r(this);
          0 < i.parents(s).length ||
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
              ((t = 'fitvid' + r.fn.fitVids._count),
              i.attr('name', t),
              r.fn.fitVids._count++),
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
    (r.fn.fitVids._count = 0);
})(window.jQuery || window.Zepto),
  async function fetchQuestions() {
    const e = await fetch('http://localhost:8055/items/questions');
    var t = (await e.json()).data;
    availableQuestions = [...t];
  };
const question = document.querySelector('#question'),
  choices = Array.from(document.querySelectorAll('.choice-text'));
let currentQuestion = {},
  acceptingAnswers = !0,
  questionCounter = 0,
  score = 0,
  availableQuestions = [];
const CORRECT_BONUS = 10,
  MAX_QUESTIONS = 5;
(startGame = () => {
  (questionCounter = 0),
    (score = 0),
    (availableQuestions = fetchQuestions()),
    getNewQuestion();
}),
  (getNewQuestion = () => {
    if (0 === availableQuestions.length || questionCounter >= MAX_QUESTIONS)
      return window.location.assign('/');
    questionCounter++;
    var e = Math.floor(Math.random() * availableQuestions.length);
    (currentQuestion = availableQuestions[e]),
      (question.innerText = currentQuestion.question),
      choices.forEach(e => {
        var t = e.dataset.number;
        e.innerText = currentQuestion['choice' + t];
      }),
      availableQuestions.splice(e, 1),
      (acceptingAnswers = !0);
  }),
  choices.forEach(e => {
    e.addEventListener('click', e => {
      acceptingAnswers &&
        ((acceptingAnswers = !1), e.target.dataset.number, getNewQuestion());
    });
  }),
  startGame(),
  (function (t, i) {
    var s,
      n,
      r,
      o,
      a,
      c,
      l,
      u = i.querySelector('link[rel=next]');
    function d() {
      if (404 === this.status)
        return (
          t.removeEventListener('scroll', p),
          void t.removeEventListener('resize', m)
        );
      this.response.querySelectorAll('article.post-card').forEach(function (e) {
        s.appendChild(i.importNode(e, !0));
      });
      var e = this.response.querySelector('link[rel=next]');
      e
        ? (u.href = e.href)
        : (t.removeEventListener('scroll', p),
          t.removeEventListener('resize', m)),
        (l = i.documentElement.scrollHeight),
        (o = r = !1);
    }
    function e() {
      var e;
      o ||
        (a + c <= l - n
          ? (r = !1)
          : ((o = !0),
            ((e = new t.XMLHttpRequest()).responseType = 'document'),
            e.addEventListener('load', d),
            e.open('GET', u.href),
            e.send(null)));
    }
    function h() {
      r || t.requestAnimationFrame(e), (r = !0);
    }
    function p() {
      (a = t.scrollY), h();
    }
    function m() {
      (c = t.innerHeight), (l = i.documentElement.scrollHeight), h();
    }
    !u ||
      ((s = i.querySelector('.post-feed')) &&
        ((o = r = !(n = 300)),
        (a = t.scrollY),
        (c = t.innerHeight),
        (l = i.documentElement.scrollHeight),
        t.addEventListener('scroll', p, { passive: !0 }),
        t.addEventListener('resize', m),
        h()));
  })(window, document);
//# sourceMappingURL=casper.js.map
