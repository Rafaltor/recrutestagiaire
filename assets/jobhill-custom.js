(function ($) {
	"use strict";


	$(window).on('load', function () {

		/*----------------------------------------------------*/
		/*	Modal Window
		/*----------------------------------------------------*/

		$('#preloader').delay(350).fadeOut('slow');
		$('body').delay(350).css({ 'overflow': 'visible' });
	});
	
	//toggle two classes on button element
    $('a.tag-bookmark').on('click',function () {
        $('a.tag-bookmark').addClass('selected');
    });

	
	$(window).on('scroll', function () {    
		if ($(document).scrollTop() > 220) {
		  $(".viewjob").addClass("sticky");
		} else {
		  $(".viewjob").removeClass("sticky");
		}
	});

	/*---- Bottom To Top Scroll Script ---*/
	$(window).on('scroll', function() {
		var height = $(window).scrollTop();
		if (height > 100) {
			$('#back2Top').fadeIn();
		} else {
			$('#back2Top').fadeOut();
		}
	});
	
	$("#back2Top").on('click', function(event) {
		event.preventDefault();
		$("html, body").animate({ scrollTop: 0 }, "slow");
		return false;
	});


	// Tooltip
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	});

	// Navigation
	! function (n, e, i, a) {
		n.navigation = function (t, s) {
			var o = {
				responsive: !0,
				mobileBreakpoint: 992,
				showDuration: 300,
				hideDuration: 300,
				showDelayDuration: 0,
				hideDelayDuration: 0,
				submenuTrigger: "hover",
				effect: "fade",
				submenuIndicator: !0,
				hideSubWhenGoOut: !0,
				visibleSubmenusOnMobile: !1,
				fixed: !1,
				overlay: !0,
				overlayColor: "rgba(0, 0, 0, 0.5)",
				hidden: !1,
				offCanvasSide: "left",
				onInit: function () { },
				onShowOffCanvas: function () { },
				onHideOffCanvas: function () { }
			},
				u = this,
				r = Number.MAX_VALUE,
				d = 1,
				f = "click.nav touchstart.nav",
				l = "mouseenter.nav",
				c = "mouseleave.nav";
			u.settings = {};
			var t = (n(t), t);
			n(t).find(".nav-menus-wrapper").prepend("<span class='nav-menus-wrapper-close-button'>✕</span>"), n(t).find(".nav-search").length > 0 && n(t).find(".nav-search").find("form").prepend("<span class='nav-search-close-button'>✕</span>"), u.init = function () {
				u.settings = n.extend({}, o, s), "right" == u.settings.offCanvasSide && n(t).find(".nav-menus-wrapper").addClass("nav-menus-wrapper-right"), u.settings.hidden && (n(t).addClass("navigation-hidden"), u.settings.mobileBreakpoint = 99999), v(), u.settings.fixed && n(t).addClass("navigation-fixed"), n(t).find(".nav-toggle").on("click touchstart", function (n) {
					n.stopPropagation(), n.preventDefault(), u.showOffcanvas(), s !== a && u.callback("onShowOffCanvas")
				}), n(t).find(".nav-menus-wrapper-close-button").on("click touchstart", function () {
					u.hideOffcanvas(), s !== a && u.callback("onHideOffCanvas")
				}), n(t).find(".nav-search-button").on("click touchstart", function (n) {
					n.stopPropagation(), n.preventDefault(), u.toggleSearch()
				}), n(t).find(".nav-search-close-button").on("click touchstart", function () {
					u.toggleSearch()
				}), n(t).find(".megamenu-tabs").length > 0 && y(), n(e).resize(function () {
					m(), C()
				}), m(), s !== a && u.callback("onInit")
			};
			var v = function () {
				n(t).find("li").each(function () {
					n(this).children(".nav-dropdown,.megamenu-panel").length > 0 && (n(this).children(".nav-dropdown,.megamenu-panel").addClass("nav-submenu"), u.settings.submenuIndicator && n(this).children("a").append("<span class='submenu-indicator'><span class='submenu-indicator-chevron'></span></span>"))
				})
			};
			u.showSubmenu = function (e, i) {
				g() > u.settings.mobileBreakpoint && n(t).find(".nav-search").find("form").slideUp(), "fade" == i ? n(e).children(".nav-submenu").stop(!0, !0).delay(u.settings.showDelayDuration).fadeIn(u.settings.showDuration) : n(e).children(".nav-submenu").stop(!0, !0).delay(u.settings.showDelayDuration).slideDown(u.settings.showDuration), n(e).addClass("nav-submenu-open")
			}, u.hideSubmenu = function (e, i) {
				"fade" == i ? n(e).find(".nav-submenu").stop(!0, !0).delay(u.settings.hideDelayDuration).fadeOut(u.settings.hideDuration) : n(e).find(".nav-submenu").stop(!0, !0).delay(u.settings.hideDelayDuration).slideUp(u.settings.hideDuration), n(e).removeClass("nav-submenu-open").find(".nav-submenu-open").removeClass("nav-submenu-open")
			};
			var h = function () {
				n("body").addClass("no-scroll"), u.settings.overlay && (n(t).append("<div class='nav-overlay-panel'></div>"), n(t).find(".nav-overlay-panel").css("background-color", u.settings.overlayColor).fadeIn(300).on("click touchstart", function (n) {
					u.hideOffcanvas()
				}))
			},
				p = function () {
					n("body").removeClass("no-scroll"), u.settings.overlay && n(t).find(".nav-overlay-panel").fadeOut(400, function () {
						n(this).remove()
					})
				};
			u.showOffcanvas = function () {
				h(), "left" == u.settings.offCanvasSide ? n(t).find(".nav-menus-wrapper").css("transition-property", "left").addClass("nav-menus-wrapper-open") : n(t).find(".nav-menus-wrapper").css("transition-property", "right").addClass("nav-menus-wrapper-open")
			}, u.hideOffcanvas = function () {
				n(t).find(".nav-menus-wrapper").removeClass("nav-menus-wrapper-open").on("webkitTransitionEnd moztransitionend transitionend oTransitionEnd", function () {
					n(t).find(".nav-menus-wrapper").css("transition-property", "none").off()
				}), p()
			}, u.toggleOffcanvas = function () {
				g() <= u.settings.mobileBreakpoint && (n(t).find(".nav-menus-wrapper").hasClass("nav-menus-wrapper-open") ? (u.hideOffcanvas(), s !== a && u.callback("onHideOffCanvas")) : (u.showOffcanvas(), s !== a && u.callback("onShowOffCanvas")))
			}, u.toggleSearch = function () {
				"none" == n(t).find(".nav-search").find("form").css("display") ? (n(t).find(".nav-search").find("form").slideDown(), n(t).find(".nav-submenu").fadeOut(200)) : n(t).find(".nav-search").find("form").slideUp()
			};
			var m = function () {
				u.settings.responsive ? (g() <= u.settings.mobileBreakpoint && r > u.settings.mobileBreakpoint && (n(t).addClass("navigation-portrait").removeClass("navigation-landscape"), D()), g() > u.settings.mobileBreakpoint && d <= u.settings.mobileBreakpoint && (n(t).addClass("navigation-landscape").removeClass("navigation-portrait"), k(), p(), u.hideOffcanvas()), r = g(), d = g()) : k()
			},
				b = function () {
					n("body").on("click.body touchstart.body", function (e) {
						0 === n(e.target).closest(".navigation").length && (n(t).find(".nav-submenu").fadeOut(), n(t).find(".nav-submenu-open").removeClass("nav-submenu-open"), n(t).find(".nav-search").find("form").slideUp())
					})
				},
				g = function () {
					return e.innerWidth || i.documentElement.clientWidth || i.body.clientWidth
				},
				w = function () {
					n(t).find(".nav-menu").find("li, a").off(f).off(l).off(c)
				},
				C = function () {
					if (g() > u.settings.mobileBreakpoint) {
						var e = n(t).outerWidth(!0);
						n(t).find(".nav-menu").children("li").children(".nav-submenu").each(function () {
							n(this).parent().position().left + n(this).outerWidth() > e ? n(this).css("right", 0) : n(this).css("right", "auto")
						})
					}
				},
				y = function () {
					function e(e) {
						var i = n(e).children(".megamenu-tabs-nav").children("li"),
							a = n(e).children(".megamenu-tabs-pane");
						n(i).on("click.tabs touchstart.tabs", function (e) {
							e.stopPropagation(), e.preventDefault(), n(i).removeClass("active"), n(this).addClass("active"), n(a).hide(0).removeClass("active"), n(a[n(this).index()]).show(0).addClass("active")
						})
					}
					if (n(t).find(".megamenu-tabs").length > 0)
						for (var i = n(t).find(".megamenu-tabs"), a = 0; a < i.length; a++) e(i[a])
				},
				k = function () {
					w(), n(t).find(".nav-submenu").hide(0), navigator.userAgent.match(/Mobi/i) || navigator.maxTouchPoints > 0 || "click" == u.settings.submenuTrigger ? n(t).find(".nav-menu, .nav-dropdown").children("li").children("a").on(f, function (i) {
						if (u.hideSubmenu(n(this).parent("li").siblings("li"), u.settings.effect), n(this).closest(".nav-menu").siblings(".nav-menu").find(".nav-submenu").fadeOut(u.settings.hideDuration), n(this).siblings(".nav-submenu").length > 0) {
							if (i.stopPropagation(), i.preventDefault(), "none" == n(this).siblings(".nav-submenu").css("display")) return u.showSubmenu(n(this).parent("li"), u.settings.effect), C(), !1;
							if (u.hideSubmenu(n(this).parent("li"), u.settings.effect), "_blank" == n(this).attr("target") || "blank" == n(this).attr("target")) e.open(n(this).attr("href"));
							else {
								if ("#" == n(this).attr("href") || "" == n(this).attr("href")) return !1;
								e.location.href = n(this).attr("href")
							}
						}
					}) : n(t).find(".nav-menu").find("li").on(l, function () {
						u.showSubmenu(this, u.settings.effect), C()
					}).on(c, function () {
						u.hideSubmenu(this, u.settings.effect)
					}), u.settings.hideSubWhenGoOut && b()
				},
				D = function () {
					w(), n(t).find(".nav-submenu").hide(0), u.settings.visibleSubmenusOnMobile ? n(t).find(".nav-submenu").show(0) : (n(t).find(".nav-submenu").hide(0), n(t).find(".submenu-indicator").removeClass("submenu-indicator-up"), u.settings.submenuIndicator ? n(t).find(".submenu-indicator").on(f, function (e) {
						return e.stopPropagation(), e.preventDefault(), u.hideSubmenu(n(this).parent("a").parent("li").siblings("li"), "slide"), u.hideSubmenu(n(this).closest(".nav-menu").siblings(".nav-menu").children("li"), "slide"), "none" == n(this).parent("a").siblings(".nav-submenu").css("display") ? (n(this).addClass("submenu-indicator-up"), n(this).parent("a").parent("li").siblings("li").find(".submenu-indicator").removeClass("submenu-indicator-up"), n(this).closest(".nav-menu").siblings(".nav-menu").find(".submenu-indicator").removeClass("submenu-indicator-up"), u.showSubmenu(n(this).parent("a").parent("li"), "slide"), !1) : (n(this).parent("a").parent("li").find(".submenu-indicator").removeClass("submenu-indicator-up"), void u.hideSubmenu(n(this).parent("a").parent("li"), "slide"))
					}) : k())
				};
			u.callback = function (n) {
				s[n] !== a && s[n].call(t)
			}, u.init()
		}, n.fn.navigation = function (e) {
			return this.each(function () {
				if (a === n(this).data("navigation")) {
					var i = new n.navigation(this, e);
					n(this).data("navigation", i)
				}
			})
		}
	}
	(jQuery, window, document), $(document).ready(function () {
		var $nav = $("#navigation");
		if ($nav.length) {
			$nav.navigation({ submenuIndicator: false });
		}
	});

	$(window).scroll(function () {
		var scroll = $(window).scrollTop();

		if (scroll >= 50) {
			$(".header").addClass("header-fixed");
		} else {
			$(".header").removeClass("header-fixed");
		}
	});

	// All Select Form Classes
	$('.select').select2();
	
	$(".multikeyword").select2({
		placeholder: "Job title, Company or Keyword...",
		allowClear:false
	});
	
	$('.shorting').select2();

	$(".jobcity").select2({
		placeholder: "City, Country or zip",
		allowClear:false
	});
	
	$(".jobcategory").select2({
		placeholder: "Choose Category",
		allowClear:false
	});
	
	$(".industry").select2({
		placeholder: "e.g. Software",
		allowClear:false
	});

	$("body").on('click', '.toggle-password', function () {
		$(this).toggleClass("fa-eye-slash");
		var input = $("#password-field");
		if (input.attr("type") === "password") {
			input.attr("type", "text");
		} else {
			input.attr("type", "password");
		}

	});
	
	// Date Range
	$(".choosedate").flatpickr({
		enableTime: false,
		dateFormat: "Y-m-d"
	});
	
	// Range Slider Script
	$(".salaryrange").ionRangeSlider({
		type: "double",
		min:5000,
		max:20000,
		from:7000,
		to:12000,
		prefix: "$",
		grid: false
	});
	
	// Range Slider Script
	$(".distancerange").ionRangeSlider({
		min:0,
		max:100,
		from:45,
		postfix: " km",
		grid: false
	});
	
	// Brand Slider
	$('.brand_slider').slick({
		  centerMode: true,
		  centerPadding: '10px',
		  arrows: false,
		  dots: false,
		  autoplay:true,
		  infinite: true,
		  speed: 520,
		  slidesToShow: 7,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1023,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: true,
				centerPadding: '10px',
				slidesToShow:5,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: true,
				centerPadding: '10px',
				slidesToShow:4,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:600,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: true,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint: 480,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: true,
				centerPadding: '10px',
				slidesToShow: 2,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Jobs Slider
		$('.jobs_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 580,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint:1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 1023,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint:767,
			  settings: {
				arrows: false,
				dots: true,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Jobs Slider
		$('.jobs_three_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 600,
		  slidesToShow: 3,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:600,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Candidates Slider
		$('.candidates_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 575,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint:992,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:600,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Employers Slider
		$('.companies_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 590,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint:1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint:993,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:680,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Location Slider
		$('.location_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 570,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint: 575,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Location Slider
		$('.catsslider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: false,
		  dots: false,
		  infinite: true,
		  speed: 650,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint:1199,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint:992,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:680,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Reviews Slider
		$('.reviews_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 560,
		  slidesToShow: 3,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint:1400,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint:1199,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Services Slider
		$('.services_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 555,
		  slidesToShow: 3,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Freelancers Slider
		$('.freelancers_slider').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: true,
		  dots: false,
		  infinite: true,
		  speed: 555,
		  slidesToShow: 3,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1199,
			  settings: {
				arrows: true,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Companies Types
		$('.companyTypes').slick({
		  centerMode: false,
		  centerPadding: '0px',
		  autoplay:true,
		  arrows: false,
		  dots: false,
		  infinite: true,
		  speed: 555,
		  slidesToShow: 4,
		  slidesToScroll:1,
		  responsive: [
			{
			  breakpoint: 1199,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:3,
				slidesToScroll:1
			  }
			},
			{
			  breakpoint: 767,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint:575,
			  settings: {
				arrows: false,
				dots: false,
				centerMode: false,
				centerPadding: '10px',
				slidesToShow:1,
				slidesToScroll: 1
			  }
			}
		  ]
		});
		
		// Freelancing Slider
		$('.freelancing_Slider').slick({
		  dots: false,
		  arrows: false,
		  infinite: true,
		  speed:510,
		  slidesToShow: 1,
		  centerMode: false,
		  autoplay:true,
		  fade: true,
		  cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
		  touchThreshold: 100
		});
		
		// item Slide
		$('.imgSlide').slick({
		  slidesToShow:1,
		  arrows: false,
		  dots: false,
		  infinite: true,
		  speed: 500,
		  fade: true,
		  cssEase: 'ease-in-out',
		  autoplaySpeed: 2000,
		  autoplay:true
		});
		
		// Single Reviews
		$('.singleReviews').slick({
		  dots: false,
		  arrows: true,
		  infinite: true,
		  speed:510,
		  slidesToShow: 1,
		  centerMode: false,
		  autoplay:true,
		  fade: true,
		  cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
		  touchThreshold: 100
		});
		
		// Freelancing Slider
		$('.freelancingSlider').slick({
		  dots: false,
		  arrows: false,
		  infinite: true,
		  speed:510,
		  slidesToShow: 1,
		  centerMode: false,
		  autoplay:true,
		  fade: true,
		  cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
		  touchThreshold: 100
		});
		
	// ------------------ End Document ------------------ //

})(this.jQuery);