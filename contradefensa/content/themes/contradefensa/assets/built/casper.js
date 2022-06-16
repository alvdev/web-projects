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
          o = (n.customSelector && e.push(n.customSelector), '.fitvidsignore'),
          e =
            (n.ignore && (o = o + ', ' + n.ignore), s(this).find(e.join(',')));
        (e = (e = e.not('object object')).not(o)).each(function () {
          var e,
            t,
            i = s(this);
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
  (question = document.querySelector('#question')),
  (choices = Array.from(document.querySelectorAll('.choice-text')));
let currentQuestion = {},
  acceptingAnswers = !0,
  questionCounter = 0,
  score = 0,
  availableQuestions = [],
  questions = [
    {
      question: 'This is the first question',
      choice1: 'Choice 1 to first question',
      choice2: 'Choice 2 to first question',
      choice3: 'Choice 3 to first question',
      choice4: 'Choice 4 to first question',
      answer: 3,
    },
    {
      question: 'This is the second question',
      choice1: 'Choice 1 to second question',
      choice2: 'Choice 2 to second question',
      choice3: 'Choice 3 to second question',
      choice4: 'Choice 4 to second question',
      answer: 4,
    },
    {
      question: 'This is the third question',
      choice1: 'Choice 1 to third question',
      choice2: 'Choice 2 to third question',
      choice3: 'Choice 3 to third question',
      choice4: 'Choice 4 to third question',
      answer: 2,
    },
    {
      question: 'This is the fourth question',
      choice1: 'Choice 1 to fourth question',
      choice2: 'Choice 2 to fourth question',
      choice3: 'Choice 3 to fourth question',
      choice4: 'Choice 4 to fourth question',
      answer: 1,
    },
  ];
const CORRECT_BONUS = 10,
  MAX_QUESTIONS = 3;
(startGame = () => {
  (questionCounter = 0),
    (score = 0),
    (availableQuestions = [...questions]),
    console.log(availableQuestions),
    getNewQuestion();
}),
  (getNewQuestion = () => {
    questionCounter++;
    var e = Math.floor(Math.random() * availableQuestions.length);
    (currentQuestion = availableQuestions[e]),
      (question.innerText = currentQuestion.question),
      choices.forEach(e => {
        var t = e.dataset.number;
        e.innerText = currentQuestion['choice' + t];
      });
  }),
  startGame(),
  (function (t, i) {
    var o,
      n,
      s,
      r,
      c,
      a,
      u,
      h = i.querySelector('link[rel=next]');
    function d() {
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
        (r = s = !1);
    }
    function e() {
      var e;
      r ||
        (c + a <= u - n
          ? (s = !1)
          : ((r = !0),
            ((e = new t.XMLHttpRequest()).responseType = 'document'),
            e.addEventListener('load', d),
            e.open('GET', h.href),
            e.send(null)));
    }
    function l() {
      s || t.requestAnimationFrame(e), (s = !0);
    }
    function p() {
      (c = t.scrollY), l();
    }
    function m() {
      (a = t.innerHeight), (u = i.documentElement.scrollHeight), l();
    }
    !h ||
      ((o = i.querySelector('.post-feed')) &&
        ((r = s = !(n = 300)),
        (c = t.scrollY),
        (a = t.innerHeight),
        (u = i.documentElement.scrollHeight),
        t.addEventListener('scroll', p, { passive: !0 }),
        t.addEventListener('resize', m),
        l()));
  })(window, document);
//# sourceMappingURL=casper.js.map
