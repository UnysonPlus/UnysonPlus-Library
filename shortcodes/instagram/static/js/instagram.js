/**
 * Instagram Feed slider arrows. The posts are a horizontally scrolling grid (CSS scroll-snap), so
 * swiping and trackpads already work; this only shows the arrows when there is more to see, moves
 * one page per click, and disables an arrow at either end. No dependencies.
 */
(function () {
	'use strict';

	function setup(root) {
		if (root.getAttribute('data-igf-ready')) { return; }
		root.setAttribute('data-igf-ready', '1');
		var track = root.querySelector('.igf__track');
		var prev = root.querySelector('.igf__arrow--prev');
		var next = root.querySelector('.igf__arrow--next');
		if (!track || !prev || !next) { return; }

		function update() {
			var max = track.scrollWidth - track.clientWidth;
			var more = max > 4;
			prev.hidden = !more;
			next.hidden = !more;
			prev.disabled = track.scrollLeft <= 4;
			next.disabled = track.scrollLeft >= max - 4;
		}
		function page(dir) {
			track.scrollBy({ left: dir * track.clientWidth, behavior: 'smooth' });
		}
		prev.addEventListener('click', function () { page(-1); });
		next.addEventListener('click', function () { page(1); });
		track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
		window.addEventListener('resize', update);
		update();
	}

	function init() {
		var list = document.querySelectorAll('.igf--slider');
		for (var i = 0; i < list.length; i++) { setup(list[i]); }
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
