( function () {
	'use strict';

	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function ready( callback ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', callback, { once: true } );
			return;
		}
		callback();
	}

	function getHeaderOffset() {
		var value = window
			.getComputedStyle( document.documentElement )
			.getPropertyValue( '--yke-header-offset' )
			.trim();

		if ( value ) {
			return parseFloat( value ) || 0;
		}

		return 0;
	}

	function setupHeaderState() {
		var header = document.querySelector( '.yke-header' );
		var ticking = false;

		if ( ! header ) {
			return;
		}

		function updateHeaderState() {
			ticking = false;
			document.body.classList.toggle( 'yke-header-compact', window.scrollY > 24 );
		}

		function requestUpdate() {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( updateHeaderState );
		}

		window.addEventListener( 'scroll', requestUpdate, { passive: true } );
		window.addEventListener( 'resize', requestUpdate );
		updateHeaderState();
	}

	function markRevealGroups() {
		var groups = document.querySelectorAll(
			'.yke-marketing-band__grid--media, .yke-marketing-copy--stacked, .yke-marketing-stack, .yke-contact-stack, .yke-proof-band, .yke-directory-grid--products, .yke-trust-grid--visual, .yke-market-grid--visual, .yke-case-teaser-grid, .yke-scene-gallery--proof, .yke-scene-gallery--trust, .yke-case-archive-query .wp-block-post-template, .yke-decision-strip, .yke-stat-band, .yke-hot-products--five, .yke-home-company, .yke-home-advantages, .yke-cert-scroll--grid, .yke-project-grid--homepage'
		);

		groups.forEach( function ( group ) {
			group.classList.add( 'yke-motion-group' );

			Array.from( group.children ).forEach( function ( child, index ) {
				if ( child.classList.contains( 'yke-motion-item' ) ) {
					return;
				}
				child.classList.add( 'yke-motion-item' );
				child.style.setProperty( '--yke-motion-index', String( index ) );
			} );
		} );

		document.querySelectorAll(
			'.yke-marketing-band__grid--media > .yke-marketing-copy, .yke-marketing-band__grid--media > .yke-marketing-visual, .yke-marketing-band__grid--media > .yke-marketing-stack, .yke-contact-layout--media > .yke-contact-stack, .yke-contact-layout--media > .yke-marketing-panel, .yke-section-head'
		).forEach( function ( element ) {
			element.classList.add( 'yke-motion-item' );
		} );
	}

	function setupRevealObserver() {
		var items = document.querySelectorAll( '.yke-motion-item' );
		if ( ! items.length ) {
			return;
		}

		if ( reduceMotion.matches || ! ( 'IntersectionObserver' in window ) ) {
			items.forEach( function ( item ) {
				item.classList.add( 'is-visible' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				} );
			},
			{
				rootMargin: '0px 0px -10% 0px',
				threshold: 0.18
			}
		);

		items.forEach( function ( item ) {
			var rect = item.getBoundingClientRect();
			if ( rect.top < window.innerHeight * 0.92 ) {
				item.classList.add( 'is-visible' );
				return;
			}
			observer.observe( item );
		} );
	}

	function setupPointerTilt() {
		if ( reduceMotion.matches ) {
			return;
		}

		var cards = document.querySelectorAll(
			'.yke-directory-card--visual, .yke-trust-card--visual, .yke-market-card--visual, .yke-case-teaser, .yke-case-card, .home .yke-project-card--homepage'
		);

		cards.forEach( function ( card ) {
			card.addEventListener( 'pointermove', function ( event ) {
				if ( window.innerWidth < 980 ) {
					return;
				}

				var rect = card.getBoundingClientRect();
				var x = event.clientX - rect.left;
				var y = event.clientY - rect.top;
				var rx = ( y / rect.height - 0.5 ) * -4;
				var ry = ( x / rect.width - 0.5 ) * 5;

				card.style.setProperty( '--yke-tilt-x', rx.toFixed( 2 ) + 'deg' );
				card.style.setProperty( '--yke-tilt-y', ry.toFixed( 2 ) + 'deg' );
				card.classList.add( 'is-tilting' );
			} );

			card.addEventListener( 'pointerleave', function () {
				card.style.removeProperty( '--yke-tilt-x' );
				card.style.removeProperty( '--yke-tilt-y' );
				card.classList.remove( 'is-tilting' );
			} );
		} );
	}

	function setupHomeHoverState() {
		document
			.querySelectorAll(
				'.home .yke-panel, .home .yke-trust-card, .home .yke-product-card, .home .yke-product-cta-card, .home .yke-case-teaser'
				+ ', .home .yke-project-card--homepage, .home .yke-cert-item--placeholder, .home .yke-stat-item'
			)
			.forEach( function ( card ) {
				card.addEventListener( 'pointerenter', function () {
					card.classList.add( 'is-hovered' );
				} );
				card.addEventListener( 'pointerleave', function () {
					card.classList.remove( 'is-hovered' );
				} );
			} );
	}

	function setupButtonPress() {
		document.querySelectorAll( '.yke-btn-primary, .yke-btn-outline, .yke-text-link' ).forEach( function ( button ) {
			button.addEventListener( 'pointerdown', function () {
				button.classList.add( 'is-pressed' );
			} );
			button.addEventListener( 'pointerup', function () {
				button.classList.remove( 'is-pressed' );
			} );
			button.addEventListener( 'pointerleave', function () {
				button.classList.remove( 'is-pressed' );
			} );
		} );
	}

	function setupHomepageHero() {
		var hero = document.querySelector( '[data-yke-hero="homepage"]' );
		var slides;
		var contentSlides;
		var dots;
		var prevButton;
		var nextButton;
		var progressBar;
		var activeIndex = 0;
		var timerId = null;
		var timeoutId = null;
		var autoplayMs = 5600;
		var paused = false;

		if ( ! hero ) {
			return;
		}

		slides = Array.from( hero.querySelectorAll( '.yke-hero-slide' ) );
		contentSlides = Array.from( hero.querySelectorAll( '.yke-hero-slide-content' ) );
		dots = Array.from( hero.querySelectorAll( '.yke-hero-dot' ) );
		prevButton = hero.querySelector( '.yke-hero-arrow--prev' );
		nextButton = hero.querySelector( '.yke-hero-arrow--next' );
		progressBar = hero.querySelector( '.yke-hero-progress__bar' );

		if ( slides.length < 2 || slides.length !== contentSlides.length ) {
			return;
		}

		function resetProgress( animate ) {
			if ( ! progressBar ) {
				return;
			}

			progressBar.style.transition = 'none';
			progressBar.style.transform = 'scaleX(0)';

			if ( ! animate || reduceMotion.matches ) {
				return;
			}

			window.requestAnimationFrame( function () {
				progressBar.style.transition = 'transform ' + autoplayMs + 'ms linear';
				progressBar.style.transform = 'scaleX(1)';
			} );
		}

		function clearTimers() {
			if ( timerId ) {
				window.clearInterval( timerId );
				timerId = null;
			}
			if ( timeoutId ) {
				window.clearTimeout( timeoutId );
				timeoutId = null;
			}
		}

		function setSlide( index ) {
			activeIndex = ( index + slides.length ) % slides.length;

			slides.forEach( function ( slide, slideIndex ) {
				slide.classList.toggle( 'yke-hero-slide--active', slideIndex === activeIndex );
			} );

			contentSlides.forEach( function ( slide, slideIndex ) {
				slide.classList.toggle( 'yke-hero-slide-content--active', slideIndex === activeIndex );
				slide.setAttribute( 'aria-hidden', slideIndex === activeIndex ? 'false' : 'true' );
			} );

			dots.forEach( function ( dot, dotIndex ) {
				var isActive = dotIndex === activeIndex;
				dot.classList.toggle( 'yke-hero-dot--active', isActive );
				dot.setAttribute( 'aria-current', isActive ? 'true' : 'false' );
			} );

			if ( ! paused ) {
				resetProgress( true );
			}
		}

		function nextSlide() {
			setSlide( activeIndex + 1 );
		}

		function startAutoplay() {
			clearTimers();

			if ( reduceMotion.matches || paused ) {
				resetProgress( false );
				return;
			}

			resetProgress( true );
			timerId = window.setInterval( nextSlide, autoplayMs );
		}

		function pauseAutoplay() {
			paused = true;
			clearTimers();
			resetProgress( false );
		}

		function resumeAutoplay() {
			if ( reduceMotion.matches ) {
				return;
			}

			paused = false;
			timeoutId = window.setTimeout( startAutoplay, 160 );
		}

		if ( prevButton ) {
			prevButton.addEventListener( 'click', function () {
				setSlide( activeIndex - 1 );
				startAutoplay();
			} );
		}

		if ( nextButton ) {
			nextButton.addEventListener( 'click', function () {
				nextSlide();
				startAutoplay();
			} );
		}

		dots.forEach( function ( dot, index ) {
			dot.addEventListener( 'click', function () {
				setSlide( index );
				startAutoplay();
			} );
		} );

		hero.addEventListener( 'mouseenter', pauseAutoplay );
		hero.addEventListener( 'mouseleave', resumeAutoplay );
		hero.addEventListener( 'focusin', pauseAutoplay );
		hero.addEventListener( 'focusout', function ( event ) {
			if ( hero.contains( event.relatedTarget ) ) {
				return;
			}
			resumeAutoplay();
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				pauseAutoplay();
				return;
			}

			paused = false;
			startAutoplay();
		} );

		setSlide( 0 );
		startAutoplay();
	}

	function collectScrollSections() {
		var sections = [];
		var hero = document.querySelector( '.yke-page-hero' );
		var homeHero = document.querySelector( '.home [data-yke-hero="homepage"]' );

		if ( homeHero ) {
			sections.push( homeHero );
		}

		if ( hero ) {
			sections.push( hero );
		}

		document.querySelectorAll(
			'.home .yke-section, .home .yke-cta-band, .yke-marketing-shell > .yke-marketing-band, .yke-marketing-shell > .yke-page-section, .yke-marketing-shell > .yke-model-hero, .yke-case-archive-intro'
		).forEach( function ( section ) {
			if ( sections.indexOf( section ) === -1 ) {
				sections.push( section );
			}
		} );

		return sections;
	}

	function getSectionLabel( section, index ) {
		var lang = document.documentElement.lang || '';
		var fallback = lang.indexOf( 'zh' ) === 0 ? '章节 ' + ( index + 1 ) : 'Section ' + ( index + 1 );
		var node = section.querySelector( 'h1, h2, .yke-kicker, h3' );

		if ( ! node ) {
			return fallback;
		}

		var label = ( node.textContent || '' ).replace( /\s+/g, ' ' ).trim();
		if ( ! label ) {
			return fallback;
		}

		return label.length > 18 ? label.slice( 0, 18 ) + '…' : label;
	}

	function setupScrollNavigation() {
		var shell = document.querySelector( '.home .wp-site-blocks, .yke-marketing-shell, .yke-case-archive-intro' );
		if ( ! shell ) {
			return;
		}

		var sections = collectScrollSections();
		var homeHero = document.querySelector( '.home [data-yke-hero="homepage"]' );
		var isHomepageRail = !! homeHero;
		if ( ! sections.length ) {
			return;
		}

		var lang = document.documentElement.lang || '';
		var progress = document.createElement( 'div' );
		progress.className = 'yke-scroll-progress';
		progress.innerHTML = '<span class="yke-scroll-progress__bar"></span>';

		var rail = document.createElement( 'nav' );
		rail.className = 'yke-scroll-rail';
		if ( isHomepageRail ) {
			rail.classList.add( 'yke-scroll-rail--home' );
		}
		rail.setAttribute( 'aria-label', lang.indexOf( 'zh' ) === 0 ? '页面区段导航' : 'Page section navigation' );
		rail.innerHTML = '<div class="yke-scroll-rail__track"></div><div class="yke-scroll-rail__stops"></div>';

		var stops = rail.querySelector( '.yke-scroll-rail__stops' );
		var progressBar = progress.querySelector( '.yke-scroll-progress__bar' );
		var buttons = [];
		var ticking = false;

		sections.forEach( function ( section, index ) {
			var label = getSectionLabel( section, index );
			var button = document.createElement( 'button' );
			var dot = document.createElement( 'span' );
			var labelNode = document.createElement( 'span' );

			section.dataset.ykeSectionLabel = label;
			button.type = 'button';
			button.className = 'yke-scroll-rail__stop';
			button.setAttribute( 'aria-label', label );
			dot.className = 'yke-scroll-rail__dot';
			labelNode.className = 'yke-scroll-rail__label';
			labelNode.textContent = label;
			button.appendChild( dot );
			button.appendChild( labelNode );
			button.addEventListener( 'click', function () {
				var top = window.scrollY + section.getBoundingClientRect().top - getHeaderOffset() - 18;
				window.scrollTo( {
					top: Math.max( top, 0 ),
					behavior: reduceMotion.matches ? 'auto' : 'smooth'
				} );
			} );

			stops.appendChild( button );
			buttons.push( button );
		} );

		document.body.appendChild( progress );
		document.body.appendChild( rail );

		function setRailVisibility( isVisible ) {
			if ( ! isHomepageRail ) {
				return;
			}

			rail.classList.toggle( 'is-past-hero', isVisible );
			rail.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );

			buttons.forEach( function ( button ) {
				button.disabled = ! isVisible;
				button.tabIndex = isVisible ? 0 : -1;
			} );
		}

		function updateScrollState() {
			ticking = false;

			var scrollable = Math.max( document.documentElement.scrollHeight - window.innerHeight, 1 );
			var progressRatio = Math.min( Math.max( window.scrollY / scrollable, 0 ), 1 );
			var headerOffset = getHeaderOffset();
			var activeIndex = 0;
			var showRail = true;

			if ( isHomepageRail ) {
				showRail = homeHero.getBoundingClientRect().bottom <= headerOffset;
				setRailVisibility( showRail );
			}

			sections.forEach( function ( section, index ) {
				var rect = section.getBoundingClientRect();
				var anchorLine = headerOffset + window.innerHeight * 0.3;
				if ( rect.top <= anchorLine ) {
					activeIndex = index;
				}
			} );

			progressBar.style.width = String( progressRatio * 100 ) + '%';

			sections.forEach( function ( section, index ) {
				section.classList.toggle( 'is-active-band', index === activeIndex );
			} );

			buttons.forEach( function ( button, index ) {
				button.classList.toggle( 'is-active', index === activeIndex );
				if ( index === activeIndex ) {
					button.setAttribute( 'aria-current', 'true' );
				} else {
					button.removeAttribute( 'aria-current' );
				}
			} );
		}

		function requestUpdate() {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( updateScrollState );
		}

		window.addEventListener( 'scroll', requestUpdate, { passive: true } );
		window.addEventListener( 'resize', requestUpdate );
		updateScrollState();
	}

	function setupMediaParallax() {
		if ( reduceMotion.matches ) {
			return;
		}

		var mediaBlocks = Array.from(
			document.querySelectorAll(
				'.yke-marketing-visual, .yke-directory-card__media, .yke-trust-card__media, .yke-market-card__media, .yke-case-teaser__media, .yke-scene-gallery__item, .yke-case-card-img'
			)
		);

		if ( ! mediaBlocks.length ) {
			return;
		}

		var ticking = false;

		function render() {
			ticking = false;

			mediaBlocks.forEach( function ( block ) {
				var rect = block.getBoundingClientRect();
				var centerOffset;
				var shift;

				if ( rect.bottom < -120 || rect.top > window.innerHeight + 120 ) {
					return;
				}

				centerOffset = ( rect.top + rect.height / 2 - window.innerHeight / 2 ) / window.innerHeight;
				shift = Math.max( -14, Math.min( 14, centerOffset * -18 ) );
				block.style.setProperty( '--yke-parallax-shift', shift.toFixed( 2 ) + 'px' );
			} );
		}

		function requestRender() {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( render );
		}

		window.addEventListener( 'scroll', requestRender, { passive: true } );
		window.addEventListener( 'resize', requestRender );
		render();
	}

	function setupProductsReadMore() {
		var buttons = document.querySelectorAll( '.yke-read-more-btn' );
		if ( ! buttons.length ) {
			return;
		}

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var card = btn.closest( '.yke-product-line-card' );
				if ( ! card ) {
					return;
				}
				var body = card.querySelector( '.yke-product-line-card-body' );
				if ( ! body ) {
					return;
				}
				var readMore = btn.getAttribute( 'data-read-more' ) || btn.textContent;
				var readLess = btn.getAttribute( 'data-read-less' ) || readMore;
				var existing = body.querySelector( '.yke-product-line-card-models-expanded' );
				if ( existing ) {
					var isOpen = existing.classList.toggle( 'is-open' );
					btn.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
					btn.textContent = isOpen ? readLess : readMore;
					return;
				}
				var list = card.querySelector( '.yke-product-line-card-models' );
				if ( ! list ) {
					return;
				}
				var wrapper = document.createElement( 'div' );
				wrapper.className = 'yke-product-line-card-models-expanded';
				Array.prototype.forEach.call( list.querySelectorAll( 'li a' ), function ( anchor ) {
					var clone = document.createElement( 'a' );
					clone.setAttribute( 'href', anchor.getAttribute( 'href' ) );
					clone.textContent = ( anchor.textContent || '' ).replace( /\s+/g, ' ' ).trim();
					wrapper.appendChild( clone );
				} );
				btn.parentNode.insertBefore( wrapper, btn.nextSibling );
				window.requestAnimationFrame( function () {
					wrapper.classList.add( 'is-open' );
				} );
				btn.setAttribute( 'aria-expanded', 'true' );
				btn.textContent = readLess;
			} );
		} );
	}

	/**
	 * Toggle the model popover on touch / hover-none devices by clicking the card.
	 * Desktop uses CSS :hover + :focus-within, so JS only kicks in for `(hover: none)`.
	 */
	function setupProductHoverCards() {
		var cards = document.querySelectorAll( '.yke-product-hover-card' );
		if ( ! cards.length ) {
			return;
		}

		function isTouchLike() {
			return window.matchMedia && window.matchMedia( '(hover: none)' ).matches;
		}

		cards.forEach( function ( card ) {
			var popover = card.querySelector( '.yke-product-hover-popover' );
			var link = card.querySelector( '.yke-product-hover-card-link' );
			if ( ! popover || ! link ) {
				return;
			}

			// 触屏：点击产品线卡切换浮层展开（点击浮层内链接不阻止默认跳转）
			link.addEventListener( 'click', function ( event ) {
				if ( ! isTouchLike() ) {
					return;
				}
				// 若点中的是浮层里的链接，不阻止默认行为（让链接跳转）
				if ( event.target.closest( '.yke-product-hover-popover a' ) ) {
					return;
				}
				event.preventDefault();
				document.querySelectorAll( '.yke-product-hover-popover.is-open-mobile' ).forEach( function ( p ) {
					if ( p !== popover ) {
						p.classList.remove( 'is-open-mobile' );
					}
				} );
				popover.classList.toggle( 'is-open-mobile' );
			} );

			// 触屏：点击浮层内的链接确保正常跳转
			popover.addEventListener( 'click', function ( event ) {
				var anchor = event.target.closest( 'a' );
				if ( anchor ) {
					event.stopPropagation();
				}
			} );
		} );
	}

	function setupCaseStudyDetailLayout() {
		var shell = document.querySelector( '.yke-case-single-shell' );
		if ( ! shell ) {
			return;
		}
		var columns = shell.querySelector( '.yke-case-single-columns' );
		var detailSection = shell.querySelector( '.yke-page-section--case-detail' );
		if ( ! columns || ! detailSection ) {
			return;
		}
		if ( detailSection.parentNode === columns.parentNode ) {
			return;
		}
		columns.parentNode.insertBefore( detailSection, columns.nextSibling );
	}

	function setupLanguageDropdown() {
		var dropdowns = document.querySelectorAll( '.yke-lang-dropdown' );
		if ( ! dropdowns.length ) {
			return;
		}

		dropdowns.forEach( function ( dropdown ) {
			var trigger = dropdown.querySelector( '.yke-lang-dropdown__trigger' );
			if ( ! trigger ) {
				return;
			}

			trigger.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				var isOpen = dropdown.classList.toggle( 'is-open' );
				trigger.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			} );

			dropdown.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
			} );
		} );

		document.addEventListener( 'click', function () {
			dropdowns.forEach( function ( dropdown ) {
				dropdown.classList.remove( 'is-open' );
				var trigger = dropdown.querySelector( '.yke-lang-dropdown__trigger' );
				if ( trigger ) {
					trigger.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Escape' ) {
				return;
			}
			dropdowns.forEach( function ( dropdown ) {
				if ( dropdown.classList.contains( 'is-open' ) ) {
					dropdown.classList.remove( 'is-open' );
					var trigger = dropdown.querySelector( '.yke-lang-dropdown__trigger' );
					if ( trigger ) {
						trigger.setAttribute( 'aria-expanded', 'false' );
						trigger.focus();
					}
				}
			} );
		} );
	}

	ready( function () {
		if ( ! reduceMotion.matches ) {
			document.documentElement.classList.add( 'yke-motion-enabled' );
		}
		setupHeaderState();
		markRevealGroups();
		setupRevealObserver();
		setupPointerTilt();
		setupHomeHoverState();
		setupButtonPress();
		setupHomepageHero();
		setupScrollNavigation();
		setupMediaParallax();
		setupProductsReadMore();
		setupProductHoverCards();
		setupCaseStudyDetailLayout();
		setupLanguageDropdown();
	} );
}() );
