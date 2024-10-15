export default function TabsDragScroll() {
	// Selectors and DOM elements
	const TabsDragScroll = document.querySelector('.TabsDragScroll');
	if (!TabsDragScroll) return;

	const tabMenu = TabsDragScroll.querySelector('ul');

	// Dragging functionality
	let activeDrag = false;
	tabMenu.addEventListener('mousemove', (event) => {
		if (!activeDrag) return;
		tabMenu.scrollLeft -= event.movementX;
		// iconVisibility();
		tabMenu.classList.add('dragging');
	});
	document.addEventListener('mouseup', () => {
		activeDrag = false;
		tabMenu.classList.remove('dragging');
	});
	tabMenu.addEventListener('mousedown', () => {
		activeDrag = true;
	});

	// Scroll to <li> on click
	const scrollToLi = (li) => {
		const liLeft = li.offsetLeft;
		const liWidth = li.offsetWidth;
		const tabCenter = (tabMenu.clientWidth / 2) - (liWidth / 2);

		tabMenu.scrollTo({
			left: liLeft - tabCenter,
			behavior: 'smooth',
		});
	};

	// Add click event listeners to all <li> elements
	tabMenu.querySelectorAll('li').forEach((li) => {
		li.addEventListener('click', () => scrollToLi(li));
	});
}
