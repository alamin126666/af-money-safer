/* AF MONEY SAFER — custom Lottie icons, hand-authored JSON.
   Same 24x24 line icons as the static set (same paths, stroke 2, round caps),
   with a slow always-looping motion. Stroke colour is the placeholder
   [0.999,0.999,0.999,1] — core.js swaps it for the live text colour at load,
   so active/inactive + light/dark all work. No expressions, no effects:
   transform-only keyframes, so the light player build can play them. */
(function () {
  var STROKE = { "ty": "st", "c": { "a": 0, "k": [0.999, 0.999, 0.999, 1] }, "o": { "a": 0, "k": 100 }, "w": { "a": 0, "k": 2 }, "lc": 2, "lj": 2, "ml": 4, "nm": "Stroke" };
  var TR = { "ty": "tr", "p": { "a": 0, "k": [0, 0] }, "a": { "a": 0, "k": [0, 0] }, "s": { "a": 0, "k": [100, 100] }, "r": { "a": 0, "k": 0 }, "o": { "a": 0, "k": 100 }, "sk": { "a": 0, "k": 0 }, "sa": { "a": 0, "k": 0 }, "nm": "Transform" };
  function path(v, i, o) {
    return { "ty": "sh", "ks": { "a": 0, "k": { "i": i || v.map(function () { return [0, 0]; }), "o": o || v.map(function () { return [0, 0]; }), "v": v, "c": false } } };
  }
  function grp(items) {
    var it = items.concat([STROKE, TR]);
    return { "ty": "gr", "nm": "g", "np": it.length, "it": it };
  }
  function layer(ind, nm, ks, shapes) {
    return { "ddd": 0, "ind": ind, "ty": 4, "nm": nm, "sr": 1, "ks": ks, "ao": 0, "shapes": shapes, "ip": 0, "op": 60, "st": 0, "bm": 0 };
  }
  function ks(p, a, s, r) {
    return { "o": { "a": 0, "k": 100 }, "r": r || { "a": 0, "k": 0 }, "p": p, "a": a || { "a": 0, "k": [12, 12, 0] }, "s": s || { "a": 0, "k": [100, 100, 100] } };
  }
  /* position keyframes: [[t, [x,y]], ...] eased, looping back to start */
  function posKF(frames) {
    var k = frames.map(function (f, i) {
      var next = frames[(i + 1) % frames.length];
      var o = { "t": f[0], "s": [f[1], f[2], 0] };
      if (i < frames.length - 1) { o.i = { "x": [0.42], "y": [1] }; o.o = { "x": [0.58], "y": [0] }; o.e = [next[1], next[2], 0]; }
      return o;
    });
    return { "a": 1, "k": k };
  }
  function scaleKF(frames) {
    var k = frames.map(function (f, i) {
      var next = frames[(i + 1) % frames.length];
      var o = { "t": f[0], "s": [f[1], f[1], 100] };
      if (i < frames.length - 1) { o.i = { "x": [0.42], "y": [1] }; o.o = { "x": [0.58], "y": [0] }; o.e = [next[1], next[1], 100]; }
      return o;
    });
    return { "a": 1, "k": k };
  }
  function top(nm, layers) {
    return { "v": "5.7.4", "fr": 30, "ip": 0, "op": 60, "w": 24, "h": 24, "nm": nm, "ddd": 0, "assets": [], "layers": layers };
  }

  window.AF_LOTTIE = {
    /* Home — house gently bobs, door dips. */
    "home": top("home", [
      layer(1, "house", ks(posKF([[0, 12, 12], [30, 12, 10.4], [60, 12, 12]])), [
        grp([
          path([[3, 11.5], [12, 4], [21, 11.5]]),
          path([[5.5, 10], [5.5, 19.5], [18.5, 19.5], [18.5, 10]])
        ])
      ]),
      layer(2, "door", ks(posKF([[0, 12, 12], [25, 12, 13], [50, 12, 12], [60, 12, 12]])), [
        grp([path([[10, 19.5], [10, 14.5], [14, 14.5], [14, 19.5]])])
      ])
    ]),
    /* Deposit — arrow bounces down, baseline stays. */
    "deposit": top("deposit", [
      layer(1, "arrow", ks(posKF([[0, 12, 12], [18, 12, 14.2], [36, 12, 12], [60, 12, 12]])), [
        grp([
          path([[12, 3], [12, 12]]),
          path([[6.5, 10.5], [12, 16], [17.5, 10.5]])
        ])
      ]),
      layer(2, "base", ks({ "a": 0, "k": [12, 12, 0] }), [
        grp([path([[4, 20.5], [20, 20.5]])])
      ])
    ]),
    /* Withdraw — arrow bounces up, baseline stays. */
    "withdraw": top("withdraw", [
      layer(1, "arrow", ks(posKF([[0, 12, 12], [18, 12, 9.8], [36, 12, 12], [60, 12, 12]])), [
        grp([
          path([[12, 16], [12, 4]]),
          path([[6.5, 9.5], [12, 4], [17.5, 9.5]])
        ])
      ]),
      layer(2, "base", ks({ "a": 0, "k": [12, 12, 0] }), [
        grp([path([[4, 20.5], [20, 20.5]])])
      ])
    ]),
    /* History — clock hand sweeps a full turn, face stays. */
    "history": top("history", [
      layer(1, "face", ks({ "a": 0, "k": [12, 12, 0] }), [
        grp([
          path(
            [[3, 12], [12, 21], [21, 12], [12, 3], [6, 5.3]],
            [[0, 0], [-4.97, 0], [0, 4.97], [4.97, 0], [2.38, -2.14]],
            [[0, 4.97], [4.97, 0], [0, -4.97], [-3.2, 0], [0, 0]]
          ),
          path([[3, 4], [3, 9], [8, 9]])
        ])
      ]),
      layer(2, "hand", ks({ "a": 0, "k": [12, 12, 0] }, { "a": 0, "k": [12, 12, 0] }, { "a": 0, "k": [100, 100, 100] }, { "a": 1, "k": [{ "i": { "x": [0.833], "y": [0.833] }, "o": { "x": [0.167], "y": [0.167] }, "t": 0, "s": [0], "e": [360] }, { "t": 60, "s": [360] }] }), [
        grp([path([[12, 7], [12, 12], [15.5, 14]])])
      ])
    ]),
    /* Profile — head gently pulses, shoulders stay. */
    "user": top("user", [
      layer(1, "head", ks({ "a": 0, "k": [12, 8, 0] }, { "a": 0, "k": [12, 8, 0] }, scaleKF([[0, 100], [30, 110], [60, 100]])), [
        grp([{ "ty": "el", "p": { "a": 0, "k": [12, 8] }, "s": { "a": 0, "k": [8, 8] }, "nm": "Head" }])
      ]),
      layer(2, "body", ks({ "a": 0, "k": [12, 12, 0] }), [
        grp([path(
          [[4, 20.5], [12, 14.5], [20, 20.5]],
          [[0, 0], [-3.8, 0], [-1.2, -4]],
          [[1.2, -4], [3.8, 0], [0, 0]]
        )])
      ])
    ])
  };
})();
