window.addEventListener('load', init);

function init(){

	if (window.location.hash == "#method-post"){
		smoothScroll('#content')
	};

	// if (window.location.hash == "#filter-search"){
		
	// };

	var backToTop = document.querySelector('#backToTop');
	backToTop.addEventListener('click', function(ev){
		smoothScroll('#header')
	}); //end event listener
}; //end init

function smoothScroll(hash){
	//ev.preventDefault();
	$('html, body').animate({
        scrollTop: $(hash).offset().top
      	}, 800, function(){
		//adds hash to browser location
			window.location.hash = hash;
		});
}