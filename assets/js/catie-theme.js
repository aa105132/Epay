/* ============================================================
   CatieCli Skin · 主题切换 (日间/夜间)
   - 原生 JS, 不依赖 jQuery
   - 初始主题 class 由 head 内联脚本提前注入(防闪烁), 本文件负责开关 UI 与切换
   - localStorage key: catie-theme  ("day" | "night")
   ============================================================ */
(function () {
  "use strict";
  var KEY = "catie-theme";
  var root = document.documentElement;

  var SUN =
    '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
    '<circle cx="12" cy="12" r="4.2"/>' +
    '<path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4l1.4-1.4M18 6l1.4-1.4"/>' +
    "</svg>";
  var MOON =
    '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M20.5 14.2A8 8 0 1 1 9.8 3.5a6.4 6.4 0 0 0 10.7 10.7z"/>' +
    "</svg>";

  function current() {
    return root.classList.contains("catie-night") ? "night" : "day";
  }

  function apply(theme) {
    root.classList.add("catie-skin");
    root.classList.toggle("catie-night", theme === "night");
    root.classList.toggle("catie-day", theme !== "night");
    try { localStorage.setItem(KEY, theme); } catch (e) {}
    var lbl = document.querySelector(".catie-theme-toggle .catie-toggle-label");
    if (lbl) lbl.textContent = theme === "night" ? "夜间" : "日间";
  }

  function build() {
    if (document.querySelector(".catie-theme-toggle")) return;
    if (!document.body) return;
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "catie-theme-toggle";
    btn.setAttribute("aria-label", "切换日间/夜间主题");
    btn.setAttribute("title", "切换日间/夜间主题");
    btn.innerHTML =
      '<span class="catie-toggle-icon catie-toggle-day">' + SUN + "</span>" +
      '<span class="catie-toggle-icon catie-toggle-night">' + MOON + "</span>" +
      '<span class="catie-toggle-label"></span>';
    btn.addEventListener("click", function () {
      apply(current() === "night" ? "day" : "night");
    });
    document.body.appendChild(btn);
    var lbl = btn.querySelector(".catie-toggle-label");
    if (lbl) lbl.textContent = current() === "night" ? "夜间" : "日间";
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", build);
  } else {
    build();
  }
})();
