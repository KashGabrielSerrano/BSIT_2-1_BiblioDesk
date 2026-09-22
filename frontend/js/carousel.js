    function toggleScroll(btnElement) {
        const wrapper = btnElement.closest('.carousel-wrapper');
        const container = wrapper.querySelector('.books');
        
        const scrollAmount = 300;
        const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;

        if (isAtEnd) {
            container.scrollTo({ left: 0, behavior: 'smooth' });
            btnElement.innerHTML = '&gt;';
        } else {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            
            setTimeout(() => {
                if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 10) {
                    btnElement.innerHTML = '&lt;';
                }
            }, 350);
        }
    }