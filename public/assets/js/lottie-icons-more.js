/* AF MONEY SAFER — custom Lottie icons, part 2: the remaining 41 icons.
   Same rules as part 1: same 24x24 line paths as the static set, transform-only
   keyframes, 2s loop at 30fps, stroke placeholder [0.999,0.999,0.999,1] swapped
   for the live text colour by core.js. Helpers come from lottie-icons.js. */
(function () {
  var B = window.AF_LOTTIE_BUILD;
  var path = B.path, grp = B.grp, layer = B.layer, ks = B.ks, posKF = B.posKF,
      scaleKF = B.scaleKF, sxyKF = B.sxyKF, rotKF = B.rotKF, ell = B.ell,
      rect = B.rect, top = B.top;
  function P(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function A(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function S() { return { "a": 0, "k": [100, 100, 100] }; }
  function bob() { return ks(posKF([[0, 12, 12], [30, 12, 10.4], [60, 12, 12]])); }
  function pulse(amp) { return ks(P(12, 12), A(12, 12), scaleKF([[0, 100], [30, amp], [60, 100]])); }

  window.AF_LOTTIE = Object.assign(window.AF_LOTTIE || {}, {
    /* Bell — swings on its hook. */
    "bell": top("bell", [
      layer(1, "bell", ks(P(12, 5), A(12, 5), S(), rotKF([[0, 0], [15, -9], [30, 0], [45, 9], [60, 0]])), [
        grp([
          path([[6, 16], [6, 11], [12, 5], [18, 11], [18, 16], [19.5, 18], [4.5, 18]],
            [[0, 0], [0, 0], [-3.31, 0], [0, -3.31], [0, 0], [0, 0], [0, 0]],
            [[0, 0], [0, -3.31], [3.31, 0], [0, 0], [0, 0], [0, 0], [0, 0]], true),
          path([[10, 21], [14, 21]])
        ])
      ])
    ]),
    /* Wallet — bobs, the clasp dot pulses. */
    "wallet": top("wallet", [
      layer(1, "body", bob(), [
        grp([
          path([[3.5, 7.5], [6, 5], [18, 5], [18, 8]],
            [[0, 0], [-1.38, 0], [0, 0], [0, 0]],
            [[0, -1.38], [0, 0], [0, 0], [0, 0]]),
          rect(12, 13.75, 17, 11.5, 2.5)
        ])
      ]),
      layer(2, "dot", ks(P(16.5, 13.8), A(16.5, 13.8), scaleKF([[0, 100], [30, 130], [60, 100]])), [
        grp([ell(16.5, 13.8, 2.4, 2.4)])
      ])
    ]),
    /* Eye — blinks. */
    "eye": top("eye", [
      layer(1, "eye", ks(P(12, 12), A(12, 12), sxyKF([[0, 100, 100], [18, 100, 100], [22, 100, 12], [26, 100, 100], [60, 100, 100]])), [
        grp([
          path([[2, 12], [12, 5], [22, 12], [12, 19]],
            [[0, 0], [-6.5, 0], [0, 0], [6.5, 0]],
            [[0, 0], [6.5, 0], [0, 0], [-6.5, 0]], true),
          ell(12, 12, 6, 6)
        ])
      ])
    ]),
    /* Eye-off — the slash slides. */
    "eyeoff": top("eyeoff", [
      layer(1, "slash", ks(posKF([[0, 12, 12], [20, 13.5, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[3, 3], [21, 21]])])
      ]),
      layer(2, "arcs", ks(P(12, 12)), [
        grp([
          path([[10.6, 6.1], [12, 6], [22, 12], [18.8, 15.8]],
            [[0, 0], [0, 0], [0, 0], [1, -0.8]],
            [[0, 0], [6.5, 0], [-1.5, 1.5], [0, 0]]),
          path([[6.3, 7.6], [2, 12], [12, 18], [16, 17.2]],
            [[0, 0], [0, 0], [-6.5, 0], [-1.2, -0.5]],
            [[0, 0], [0, 0], [1.5, 0], [0, 0]])
        ])
      ])
    ]),
    /* Mail — the envelope flap dips. */
    "mail": top("mail", [
      layer(1, "box", ks(P(12, 12)), [grp([rect(12, 12, 18, 14, 2.5)])]),
      layer(2, "flap", ks(posKF([[0, 12, 12], [20, 12, 13.5], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[4, 7.5], [12, 13.5], [20, 7.5]])])
      ])
    ]),
    /* Lock — the shackle jiggles. */
    "lock": top("lock", [
      layer(1, "body", ks(P(12, 12)), [grp([rect(12, 15.5, 15, 10, 2.5)])]),
      layer(2, "shackle", ks(posKF([[0, 12, 12], [18, 12, 10.6], [36, 12, 12], [60, 12, 12]])), [
        grp([path([[8, 10.5], [8, 8], [12, 4], [16, 8], [16, 10.5]],
          [[0, 0], [0, 0], [-2.21, 0], [0, -2.21], [0, 0]],
          [[0, 0], [0, -2.21], [2.21, 0], [0, 0], [0, 0]])])
      ])
    ]),
    /* Key — swings on its ring. */
    "key": top("key", [
      layer(1, "key", ks(P(8, 15), A(8, 15), S(), rotKF([[0, 0], [15, -10], [30, 0], [45, 10], [60, 0]])), [
        grp([
          ell(8, 15, 8, 8),
          path([[11, 12], [19.5, 3.5]]),
          path([[16, 7], [19, 10]])
        ])
      ])
    ]),
    /* Shield — gentle pulse. */
    "shield": top("shield", [
      layer(1, "shield", pulse(106), [
        grp([path([[12, 3], [20, 6], [20, 12], [12, 21.5], [4, 12], [4, 6]],
          [[0, 0], [0, 0], [0, 0], [4.8, -1.5], [0, 4.5], [0, 0]],
          [[0, 0], [0, 0], [0, 4.5], [-4.8, -1.5], [0, 0], [0, 0]], true)])
      ])
    ]),
    /* Check — pops. */
    "check": top("check", [
      layer(1, "check", pulse(110), [
        grp([path([[5, 12.5], [9.5, 17], [19, 7.5]])])
      ])
    ]),
    /* X — pops. */
    "x": top("x", [
      layer(1, "x", pulse(110), [
        grp([
          path([[6, 6], [18, 18]]),
          path([[18, 6], [6, 18]])
        ])
      ])
    ])
  });
})();

(function () {
  var B = window.AF_LOTTIE_BUILD;
  var path = B.path, grp = B.grp, layer = B.layer, ks = B.ks, posKF = B.posKF,
      scaleKF = B.scaleKF, sxyKF = B.sxyKF, rotKF = B.rotKF, ell = B.ell,
      rect = B.rect, top = B.top;
  function P(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function A(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function S() { return { "a": 0, "k": [100, 100, 100] }; }
  function bob() { return ks(posKF([[0, 12, 12], [30, 12, 10.4], [60, 12, 12]])); }
  function pulse(amp) { return ks(P(12, 12), A(12, 12), scaleKF([[0, 100], [30, amp], [60, 100]])); }

  window.AF_LOTTIE = Object.assign(window.AF_LOTTIE || {}, {
    /* Alert — triangle pulses, the "!" bobs. */
    "alert": top("alert", [
      layer(1, "tri", pulse(107), [
        grp([path([[12, 3.5], [22, 20], [2, 20]], null, null, true)])
      ]),
      layer(2, "bang", ks(posKF([[0, 12, 12], [20, 12, 13], [40, 12, 12], [60, 12, 12]])), [
        grp([
          path([[12, 10], [12, 14.5]]),
          path([[12, 17.5], [12, 17.6]])
        ])
      ])
    ]),
    /* Info — gentle bob. */
    "info": top("info", [
      layer(1, "info", bob(), [
        grp([
          ell(12, 12, 18, 18),
          path([[12, 11], [12, 16.5]]),
          path([[12, 7.8], [12, 7.9]])
        ])
      ])
    ]),
    /* Moon — rocks gently. */
    "moon": top("moon", [
      layer(1, "moon", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [15, -6], [30, 0], [45, 6], [60, 0]])), [
        grp([path([[20, 14.5], [9.5, 4]],
          [[-2.5, -5], [-1.5, 8]],
          [[-5, 5], [3.5, 2.5]], true)])
      ])
    ]),
    /* Sun — the rays slowly spin, core stays. */
    "sun": top("sun", [
      layer(1, "core", ks(P(12, 12)), [grp([ell(12, 12, 8, 8)])]),
      layer(2, "rays", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [60, 360]], true)), [
        grp([
          path([[12, 2.5], [12, 5]]),
          path([[12, 19], [12, 21.5]]),
          path([[2.5, 12], [5, 12]]),
          path([[19, 12], [21.5, 12]]),
          path([[5.3, 5.3], [7.1, 7.1]]),
          path([[16.9, 16.9], [18.7, 18.7]]),
          path([[5.3, 18.7], [7.1, 16.9]]),
          path([[16.9, 7.1], [18.7, 5.3]])
        ])
      ])
    ]),
    /* Globe — the meridians spin like a globe. */
    "globe": top("globe", [
      layer(1, "rim", ks(P(12, 12)), [grp([ell(12, 12, 18, 18)])]),
      layer(2, "lines", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [60, 360]], true)), [
        grp([
          path([[3, 12], [21, 12]]),
          path([[12, 3], [12, 21]],
            [[0, 0], [2.8, -2.7]],
            [[2.8, 2.7], [0, 0]]),
          path([[12, 3], [12, 21]],
            [[0, 0], [-2.8, -2.7]],
            [[-2.8, 2.7], [0, 0]])
        ])
      ])
    ]),
    /* Backspace — the X slides out and back. */
    "backspace": top("backspace", [
      layer(1, "body", ks(P(12, 12)), [
        grp([path([[9, 5], [20, 5], [21.5, 6.5], [21.5, 17.5], [20, 19], [9, 19], [3, 12]], null, null, true)])
      ]),
      layer(2, "x", ks(posKF([[0, 12, 12], [20, 10.8, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([
          path([[12.5, 9.5], [17.5, 14.5]]),
          path([[17.5, 9.5], [12.5, 14.5]])
        ])
      ])
    ]),
    /* Camera — the lens pulses. */
    "camera": top("camera", [
      layer(1, "body", ks(P(12, 12)), [
        grp([path([[4, 8], [7, 8], [8.5, 5.5], [15.5, 5.5], [17, 8], [20, 8], [20, 19], [4, 19]], null, null, true)])
      ]),
      layer(2, "lens", ks(P(12, 13), A(12, 13), scaleKF([[0, 100], [30, 118], [60, 100]])), [
        grp([ell(12, 13, 7, 7)])
      ])
    ]),
    /* Logout — the arrow slides out. */
    "logout": top("logout", [
      layer(1, "door", ks(P(12, 12)), [
        grp([path([[10, 4], [5.5, 4], [4, 5.5], [4, 18.5], [5.5, 20], [10, 20]])])
      ]),
      layer(2, "arrow", ks(posKF([[0, 12, 12], [20, 14, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([
          path([[15, 8], [19, 12], [15, 16]]),
          path([[19, 12], [9, 12]])
        ])
      ])
    ]),
    /* Chevron — nudges right. */
    "chevron": top("chevron", [
      layer(1, "chev", ks(posKF([[0, 12, 12], [20, 13.5, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[9, 6], [15, 12], [9, 18]])])
      ])
    ]),
    /* Chevron-left — nudges left. */
    "chevron-left": top("chevron-left", [
      layer(1, "chev", ks(posKF([[0, 12, 12], [20, 10.5, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[15, 6], [9, 12], [15, 18]])])
      ])
    ])
  });
})();

(function () {
  var B = window.AF_LOTTIE_BUILD;
  var path = B.path, grp = B.grp, layer = B.layer, ks = B.ks, posKF = B.posKF,
      scaleKF = B.scaleKF, sxyKF = B.sxyKF, rotKF = B.rotKF, ell = B.ell,
      rect = B.rect, top = B.top;
  function P(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function A(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function S() { return { "a": 0, "k": [100, 100, 100] }; }
  function bob() { return ks(posKF([[0, 12, 12], [30, 12, 10.4], [60, 12, 12]])); }
  function pulse(amp) { return ks(P(12, 12), A(12, 12), scaleKF([[0, 100], [30, amp], [60, 100]])); }

  window.AF_LOTTIE = Object.assign(window.AF_LOTTIE || {}, {
    /* Copy — the back sheet slides up-left. */
    "copy": top("copy", [
      layer(1, "front", ks(P(12, 12)), [grp([rect(14, 14, 12, 12, 2.5)])]),
      layer(2, "back", ks(posKF([[0, 12, 12], [20, 11, 11], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[16, 8], [16, 6.5], [13.5, 4], [6.5, 4], [4, 6.5], [4, 13.5], [6.5, 16], [8, 16]])])
      ])
    ]),
    /* Trash — the lid lifts. */
    "trash": top("trash", [
      layer(1, "bin", ks(P(12, 12)), [
        grp([
          path([[3, 6], [5, 6], [21, 6]]),
          path([[19, 6], [18, 20], [6, 20], [5, 6]]),
          path([[10, 11], [10, 17]]),
          path([[14, 11], [14, 17]])
        ])
      ]),
      layer(2, "lid", ks(posKF([[0, 12, 12], [20, 12, 10.8], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[9, 6], [9, 4], [15, 4], [15, 6]])])
      ])
    ]),
    /* Filter — the lines equalize one by one. */
    "filter": top("filter", [
      layer(1, "l1", ks(P(12, 6), A(12, 6), sxyKF([[0, 100, 100], [15, 70, 100], [30, 100, 100], [60, 100, 100]])), [
        grp([path([[4, 6], [20, 6]])])
      ]),
      layer(2, "l2", ks(P(12, 12), A(12, 12), sxyKF([[0, 100, 100], [10, 100, 100], [25, 70, 100], [40, 100, 100], [60, 100, 100]])), [
        grp([path([[8, 12], [16, 12]])])
      ]),
      layer(3, "l3", ks(P(12, 18), A(12, 18), sxyKF([[0, 100, 100], [20, 100, 100], [35, 60, 100], [50, 100, 100], [60, 100, 100]])), [
        grp([path([[11, 18], [13, 18]])])
      ])
    ]),
    /* Calendar — gentle bob. */
    "calendar": top("calendar", [
      layer(1, "cal", bob(), [
        grp([
          rect(12, 12.5, 18, 17, 2.5),
          path([[16, 2], [16, 6]]),
          path([[8, 2], [8, 6]]),
          path([[3, 10], [21, 10]])
        ])
      ])
    ]),
    /* Chart — the bars bounce in sequence. */
    "chart": top("chart", [
      layer(1, "axes", ks(P(12, 12)), [
        grp([path([[3, 3], [3, 21], [21, 21]])])
      ]),
      layer(2, "b1", ks(P(7, 15), A(7, 15), sxyKF([[0, 100, 100], [15, 100, 55], [30, 100, 100], [60, 100, 100]])), [
        grp([path([[7, 15], [7, 12]])])
      ]),
      layer(3, "b2", ks(P(12, 15), A(12, 15), sxyKF([[0, 100, 100], [10, 100, 100], [25, 100, 55], [40, 100, 100], [60, 100, 100]])), [
        grp([path([[12, 15], [12, 7]])])
      ]),
      layer(4, "b3", ks(P(17, 15), A(17, 15), sxyKF([[0, 100, 100], [20, 100, 100], [35, 100, 55], [50, 100, 100], [60, 100, 100]])), [
        grp([path([[17, 15], [17, 10]])])
      ])
    ]),
    /* Settings — the gear spokes turn. */
    "settings": top("settings", [
      layer(1, "hub", ks(P(12, 12)), [grp([ell(12, 12, 6.4, 6.4)])]),
      layer(2, "spokes", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [60, 360]], true)), [
        grp([
          path([[12, 2.8], [12, 5.5]]),
          path([[12, 18.5], [12, 21.2]]),
          path([[2.8, 12], [5.5, 12]]),
          path([[18.5, 12], [21.2, 12]]),
          path([[5.2, 5.2], [7.1, 7.1]]),
          path([[16.9, 16.9], [18.8, 18.8]]),
          path([[18.8, 5.2], [16.9, 7.1]]),
          path([[7.1, 16.9], [5.2, 18.8]])
        ])
      ])
    ]),
    /* Shield-check — gentle pulse. */
    "shield-check": top("shield-check", [
      layer(1, "sc", pulse(106), [
        grp([
          path([[12, 3], [20, 6], [20, 12], [12, 21.5], [4, 12], [4, 6]],
            [[0, 0], [0, 0], [0, 0], [4.8, -1.5], [0, 4.5], [0, 0]],
            [[0, 0], [0, 0], [0, 4.5], [-4.8, -1.5], [0, 0], [0, 0]], true),
          path([[8.5, 12], [11, 14.5], [15.5, 9.5]])
        ])
      ])
    ]),
    /* Sparkle — the stars twinkle in turn. */
    "sparkle": top("sparkle", [
      layer(1, "big", ks(P(12, 10.8), A(12, 10.8), scaleKF([[0, 100], [30, 112], [60, 100]])), [
        grp([path([[12, 3.5], [13.8, 9], [19.3, 10.8], [13.8, 12.6], [12, 18], [10.2, 12.6], [4.7, 10.8], [10.2, 9]], null, null, true)])
      ]),
      layer(2, "small", ks(P(18.5, 18.5), A(18.5, 18.5), scaleKF([[0, 100], [15, 100], [35, 125], [55, 100], [60, 100]])), [
        grp([path([[18.5, 15.5], [19.3, 17.7], [21.5, 18.5], [19.3, 19.3], [18.5, 21.5], [17.7, 19.3], [15.5, 18.5], [17.7, 17.7]], null, null, true)])
      ])
    ]),
    /* Send — the plane lifts off. */
    "send": top("send", [
      layer(1, "plane", ks(posKF([[0, 12, 12], [20, 13.5, 10.5], [40, 12, 12], [60, 12, 12]])), [
        grp([
          path([[21.5, 2.5], [11, 13]]),
          path([[21.5, 2.5], [14.8, 21.5], [11, 13], [2.5, 9.2]], null, null, true)
        ])
      ])
    ]),
    /* Plus — turns a quarter turn and back. */
    "plus": top("plus", [
      layer(1, "plus", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [30, 90], [60, 0]])), [
        grp([
          path([[12, 5], [12, 19]]),
          path([[5, 12], [19, 12]])
        ])
      ])
    ])
  });
})();

(function () {
  var B = window.AF_LOTTIE_BUILD;
  var path = B.path, grp = B.grp, layer = B.layer, ks = B.ks, posKF = B.posKF,
      scaleKF = B.scaleKF, sxyKF = B.sxyKF, rotKF = B.rotKF, ell = B.ell,
      rect = B.rect, top = B.top;
  function P(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function A(x, y) { return { "a": 0, "k": [x, y, 0] }; }
  function S() { return { "a": 0, "k": [100, 100, 100] }; }
  function bob() { return ks(posKF([[0, 12, 12], [30, 12, 10.4], [60, 12, 12]])); }
  function pulse(amp) { return ks(P(12, 12), A(12, 12), scaleKF([[0, 100], [30, amp], [60, 100]])); }

  window.AF_LOTTIE = Object.assign(window.AF_LOTTIE || {}, {
    /* Search — gentle bob. */
    "search": top("search", [
      layer(1, "mag", bob(), [
        grp([
          ell(11, 11, 14, 14),
          path([[20.5, 20.5], [16.5, 16.5]])
        ])
      ])
    ]),
    /* Refresh — spins a full turn. */
    "refresh": top("refresh", [
      layer(1, "ref", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [60, 360]], true)), [
        grp([
          path([[21, 12], [12, 21], [3, 12], [12, 3], [18.4, 5.6]],
            [[0, 0], [-4.97, 0], [0, 4.97], [4.97, 0], [-3.51, -3.51]],
            [[0, 4.97], [4.97, 0], [0, -4.97], [4.97, 0], [0, 0]]),
          path([[21, 3], [21, 9], [15, 9]])
        ])
      ])
    ]),
    /* Clock — the hand sweeps a full turn. */
    "clock": top("clock", [
      layer(1, "face", ks(P(12, 12)), [grp([ell(12, 12, 18, 18)])]),
      layer(2, "hand", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [60, 360]], true)), [
        grp([path([[12, 7], [12, 12], [15.5, 14]])])
      ])
    ]),
    /* Receipt — the text lines slide in turn. */
    "receipt": top("receipt", [
      layer(1, "paper", ks(P(12, 12)), [
        grp([path([[5, 3], [19, 3], [19, 21], [16.7, 19.4], [14.3, 21], [12, 19.4], [9.7, 21], [7.3, 19.4], [5, 21]], null, null, true)])
      ]),
      layer(2, "l1", ks(posKF([[0, 12, 12], [15, 13, 12], [30, 12, 12], [60, 12, 12]])), [
        grp([path([[9, 8], [15, 8]])])
      ]),
      layer(3, "l2", ks(posKF([[0, 12, 12], [10, 12, 12], [25, 13, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([path([[9, 12], [15, 12]])])
      ])
    ]),
    /* Grid — the tiles pop in sequence. */
    "grid": top("grid", [
      layer(1, "t1", ks(P(7, 7), A(7, 7), scaleKF([[0, 100], [15, 115], [30, 100], [60, 100]])), [grp([rect(7, 7, 7, 7, 1.5)])]),
      layer(2, "t2", ks(P(17, 7), A(17, 7), scaleKF([[0, 100], [10, 100], [25, 115], [40, 100], [60, 100]])), [grp([rect(17, 7, 7, 7, 1.5)])]),
      layer(3, "t3", ks(P(7, 17), A(7, 17), scaleKF([[0, 100], [20, 100], [35, 115], [50, 100], [60, 100]])), [grp([rect(7, 17, 7, 7, 1.5)])]),
      layer(4, "t4", ks(P(17, 17), A(17, 17), scaleKF([[0, 100], [30, 100], [45, 115], [60, 100]])), [grp([rect(17, 17, 7, 7, 1.5)])])
    ]),
    /* Phone — rocks gently (handset outline, arcs approximated). */
    "phone": top("phone", [
      layer(1, "phone", ks(P(12, 12), A(12, 12), S(), rotKF([[0, 0], [15, -7], [30, 0], [45, 7], [60, 0]])), [
        grp([path([[5, 3.5], [8.5, 3.5], [10.3, 8], [8, 9.6], [14.4, 16], [16, 13.7], [20.5, 15.5], [20.5, 19], [19.3, 21], [9, 21.8], [3, 5.7]],
          [[0, 0], [0, 0], [0, 0], [0, 0], [-0.9, -3], [0, 0], [0, 0], [0, 0], [0, 0], [2, 0.5], [-0.2, 6.3]],
          [[0, 0], [0, 0], [0, 0], [3, 0.9], [0, 0], [0, 0], [0, 0], [0, 0], [-4.8, 1.3], [-4, -0.3], [0, 0]], true)])
      ])
    ]),
    /* Star — twinkles. */
    "star": top("star", [
      layer(1, "star", ks(P(12, 12), A(12, 12), scaleKF([[0, 100], [30, 112], [60, 100]]), rotKF([[0, 0], [30, 10], [60, 0]])), [
        grp([path([[12, 3], [14.7, 8.6], [20.8, 9.4], [16.3, 13.6], [17.4, 19.6], [12, 16.6], [6.6, 19.6], [7.7, 13.6], [3.2, 9.4], [9.3, 8.6]], null, null, true)])
      ])
    ]),
    /* Arrow-right — nudges right. */
    "arrow-right": top("arrow-right", [
      layer(1, "arr", ks(posKF([[0, 12, 12], [20, 14, 12], [40, 12, 12], [60, 12, 12]])), [
        grp([
          path([[4, 12], [19.5, 12]]),
          path([[14, 6], [20, 12], [14, 18]])
        ])
      ])
    ]),
    /* Card — gentle bob. */
    "card": top("card", [
      layer(1, "card", bob(), [
        grp([
          rect(12, 12, 19, 14, 2.5),
          path([[2.5, 10], [21.5, 10]]),
          path([[6, 15], [10, 15]])
        ])
      ])
    ]),
    /* Download — arrow bounces down. */
    "download": top("download", [
      layer(1, "arrow", ks(posKF([[0, 12, 12], [18, 12, 14.2], [36, 12, 12], [60, 12, 12]])), [
        grp([
          path([[12, 3.5], [12, 15]]),
          path([[6.8, 10.5], [12, 15.7], [17.2, 10.5]])
        ])
      ]),
      layer(2, "base", ks(P(12, 12)), [
        grp([path([[4, 19.5], [20, 19.5]])])
      ])
    ]),
    /* Zap — flickers with a pulse. */
    "zap": top("zap", [
      layer(1, "zap", pulse(112), [
        grp([path([[13, 2.5], [4.5, 14], [11, 14], [10, 21.5], [18.5, 10], [12, 10]], null, null, true)])
      ])
    ])
  });
})();
