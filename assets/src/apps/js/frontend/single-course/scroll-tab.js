export default function  TabsDragScroll() {
	// dragScroll
	const TabsDragScroll = document.querySelector('.TabsDragScroll');
	if (!TabsDragScroll) {
		return;
	}
	const tabMenu = TabsDragScroll.querySelector('ul');
	const btnLeft = document.createElement('span');
	btnLeft.className = 'left-btn lp-icon-ellipsis-h';

	const btnRight = document.createElement('span');
	btnRight.className = 'right-btn lp-icon-ellipsis-h';

	TabsDragScroll.appendChild(btnLeft);
	TabsDragScroll.appendChild(btnRight);

	const iconVisibility = () => {
		let scrollLeftValue = Math.ceil(tabMenu.scrollLeft);
		let scrollableWidth = tabMenu.scrollWidth - tabMenu.clientWidth;
		btnLeft.style.display = scrollLeftValue > 0 ? 'block' : 'none';
		btnRight.style.display = scrollableWidth > scrollLeftValue ? 'block' : 'none';
	};

	btnRight.addEventListener('click', () => {
		tabMenu.scrollLeft += 150;
		iconVisibility();
		setTimeout(() => iconVisibility(), 50);
	});
	btnLeft.addEventListener('click', () => {
		tabMenu.scrollLeft -= 150;
		iconVisibility();
		setTimeout(() => iconVisibility(), 50);
	});

	window.onload = function () {
		btnRight.style.display = tabMenu.scrollWidth > tabMenu.clientWidth || tabMenu.scrollWidth >= window.innerWidth ? 'block' : 'none';
		btnLeft.style.display = tabMenu.scrollWidth >= window.innerWidth ? '' : 'none';
	};

	window.onresize = function () {
		btnRight.style.display = tabMenu.scrollWidth > tabMenu.clientWidth || tabMenu.scrollWidth >= window.innerWidth ? 'block' : 'none';
		btnLeft.style.display = tabMenu.scrollWidth >= window.innerWidth ? '' : 'none';

		let scrollLeftValue = Math.round(tabMenu.scrollLeft);
		btnLeft.style.display = scrollLeftValue > 0 ? 'block' : 'none';
	};

// Javascript to make the tab navigation draggable
	let activeDrag = false;

	tabMenu.addEventListener('mousemove', (drag) => {
		if (!activeDrag) return;
		tabMenu.scrollLeft -= drag.movementX;
		iconVisibility();

		tabMenu.classList.add('dragging');
	});

	document.addEventListener('mouseup', () => {
		activeDrag = false;

		tabMenu.classList.remove('dragging');
	});

	tabMenu.addEventListener('mousedown', () => {
		activeDrag = true;
	});
}
