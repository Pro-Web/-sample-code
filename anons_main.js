var $ = jQuery;
var AllDataKalendar;
var date = new Date();
var Year = date.getFullYear();


$(document).ready(function(){

        var kalendar_home = $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" }).val();
	
	    console.log(kalendar_home);
        
        var anonsData = new FormData();
	        anonsData.append('action', 'ajaxKalendarGet' );
	        anonsData.append('kalendar_home_data', kalendar_home);
	        anonsData.append('_csrf-frontend', $('meta[name=csrf-token]').attr("content"));

        $.ajax({
	        type: 'POST',
			url: 'kalendar/get-anons',
			data: anonsData,
			processData: false, // Не обрабатываем файлы (Don't process the files)
	        contentType: false,
			success: function (data) {
            
		 	var data = JSON.parse(data);

            AllstoredArray = data;
            var ProgramItems = '';

            console.log(AllstoredArray);

			AllstoredArray.forEach(function(item, i, AllstoredArray) {
            
            if(item.KalendarPhoto != undefined ||
               item.KalendarTitle != undefined ||
               item.KalendarTag != undefined ||
               item.KalendarMonat != undefined ||
               item.KalendarOpis != undefined )
            {
            	ProgramItems +=
            	'<div class="popup__calendar-header">Обраний день: '+item.KalendarTag+' '+item.KalendarMonat+', '+Year+' '+item.KalendarTime+'</div>'+
                    '<div class="popup__calendar-content">'+
                        '<div class="popup__calendar-text-wrapper">'+
                            '<h2 class="popup__calendar-title">'+item.KalendarTitle+'</h2>'+
                            '<p class="popup__calendar-text">'+item.KalendarOpis+'</p>'+
                            '<a class="popup__calendar-more-btn" href="/new/'+item.KalendarUrl+'.html">Детальніше</a>'+
                        '</div>'+
                        '<div class="popup__calendar-img" style="background: url(/uploads/novosti/'+item.KalendarPhoto+') center no-repeat"></div>'+
                '</div>';

		    }

			if(item.AllDataKalendar != undefined){

			AllDataKalendar = item.AllDataKalendar;

			var flags = []; //Ложим текущий месяц в массив
		    flags.push(Number($('table.ui-datepicker-calendar').find('td[data-handler^=selectDay]:eq(0)').attr('data-month')));

			AllDataKalendar.forEach(function(item, i, AllDataKalendar) {

			    var inmonat = flags.indexOf(Number(item.P0));
	            var currentDivValue, currentSobitij;

				if(inmonat != -1) {
				  currentDivValue = Number(item.P1);
				} //Проверили, есть ли в flags получаемый месяц

				currentSobitij = item.P2;

				$("table.ui-datepicker-calendar").find('td[data-year^='+Year+']').each(function(index, item){  

	                 if($(item).children('.ui-state-default').text() == currentDivValue)
	                 {
	                    $(item).children('a').addClass('has-data-post');
	                    //$(item).append('<span class="kolvo">'+currentSobitij+'</span>');
	                 }

				});

			});

			}


		    });


		    $('a.ui-datepicker-prev').bind('click', All_DataKalendarGet);
		    $('a.ui-datepicker-next').bind('click', All_DataKalendarGet);

            
            if(ProgramItems == '')
            {
              $('.screen-kalendar--intro').text('');
              $('.screen-kalendar--intro').append('<span>Анонсов нет. Выберите другой День.</span>');
              $('.screen-kalendar--intro').addClass('text');
            }
            else {
             $('.screen-kalendar--intro').html($(ProgramItems));
             $('.screen-kalendar--intro').removeClass('text');
            }
                
             
		 }

	    });



	    $("#datepicker").change( function() {

	       $("#datepicker").datepicker({ onSelect: All_DataKalendarGet() });

	       var dataformatK = $(this).datepicker({ dateFormat: "yy-mm-dd" }).val();

	       Vibor_DataKalendarGet(dataformatK);
	    });

		$("#datepicker").click( function() {

	        All_DataKalendarGet();
		});

});

    
    function All_DataKalendarGet() {
        
        setTimeout(function () {
	    	var flags = []; //Ложим текущий месяц в массив
			flags.push(Number($('table.ui-datepicker-calendar').find('td[data-handler^=selectDay]:eq(0)').attr('data-month')));

	    	AllDataKalendar.forEach(function(item, i, AllDataKalendar) {

	            var inmonat = flags.indexOf(Number(item.P0));
	            var currentDivValue, currentSobitij;

				if(inmonat != -1) {
				  currentDivValue = Number(item.P1);
				} //Проверили, есть ли в flags получаемый месяц

				currentSobitij = item.P2;

				$("table.ui-datepicker-calendar").find('td[data-year^='+Year+']').each(function(index, item){  

	                 if($(item).children('.ui-state-default').text() == currentDivValue)
	                 {
	                    $(item).children('a').addClass('has-data-post');
	                    //$(item).append('<span class="kolvo">'+currentSobitij+'</span>');
	                 }

				});

			});

        }, 20 );

    }


    function Vibor_DataKalendarGet(datavibor) {

    
    var anonsData = new FormData();
	        anonsData.append('action', 'ajaxKalendarAnonsClick' );
	        anonsData.append('kalendar_home_data', datavibor);
	        anonsData.append('_csrf-frontend', $('meta[name=csrf-token]').attr("content"));

    $.ajax({
    	 type: 'POST',
		 url: 'kalendar/get-anonsdata',
		 data: anonsData,
		 processData: false, // Не обрабатываем файлы (Don't process the files)
	     contentType: false,
		 success: function (data) {

		 	var data = JSON.parse(data);

            AllstoredArray = data;
            var ProgramItems = '';
            

			AllstoredArray.forEach(function(item, i, AllstoredArray) {
            
            	ProgramItems +=
            	'<div class="popup__calendar-header">Обраний день: '+item.KalendarTag+' '+item.KalendarMonat+', '+Year+' '+item.KalendarTime+'</div>'+
                    '<div class="popup__calendar-content">'+
                        '<div class="popup__calendar-text-wrapper">'+
                            '<h2 class="popup__calendar-title">'+item.KalendarTitle+'</h2>'+
                            '<p class="popup__calendar-text">'+item.KalendarOpis+'</p>'+
                            '<a class="popup__calendar-more-btn" href="/new/'+item.KalendarUrl+'.html">Детальніше</a>'+
                        '</div>'+
                        '<div class="popup__calendar-img" style="background: url(/uploads/novosti/'+item.KalendarPhoto+') center no-repeat"></div>'+
                '</div>';

		    });


		    $('a.ui-datepicker-prev').bind('click', All_DataKalendarGet);
		    $('a.ui-datepicker-next').bind('click', All_DataKalendarGet);

            
            if(ProgramItems == '')
            {
               $('.screen-kalendar--intro').text('');
               $('.screen-kalendar--intro').append('<span>Анонсов нет. Выберите другой День.</span>');
               $('.screen-kalendar--intro').addClass('text');
            }
            else 
            {
               $('.screen-kalendar--intro').html($(ProgramItems));
               $('.screen-kalendar--intro').removeClass('text');
            }
            
		 }

	    });

    }