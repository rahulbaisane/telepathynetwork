/*(function ($, Drupal, drupalSettings) {
  var testMe = Drupal.settings.tn_graph.test;
  alert(testMe);
)(jQuery, Drupal, drupalSettings);*/


/*var options = {
  fullWidth: true,
  axisY: {
    onlyInteger: true
  },
  plugins: [
    Chartist.plugins.tooltip()
  ],
  showArea: true,
};
var chart = new Chartist.Line('.ct-chart', {
  labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  series: [
    [{meta: 'Rahul', value: 1 }, 2, 3, 2, 4, 2, 3, 5, 4, 3]
  ]
},options);*/

/*(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tn_graph = {
    attach: function attach(context) {
      var $context = $(context);

    }
  };
})(jQuery, Drupal, drupalSettings);
*/
/*(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tn_graph = {
    attach: function (context, settings) {
      //$('input.tn_graph', context).once('tn_graph').each(function () {
        // Apply the myCustomBehaviour effect to the elements only once.
      //});
    var testMe = Drupal.drupalSettings.tn_graph.test;
  	alert(testMe);
    }
  };
})(jQuery, Drupal, drupalSettings);*/


/*Drupal.behaviors.myBehavior = {
  attach: function (context, settings) {
    // Using once() to apply the myCustomBehaviour effect when you want to run just one function.
    $('input.myCustomBehavior', context).once('myCustomBehavior').addClass('processed');

    // Using once() with more complexity.
    $('input.myCustom', context).once('mySecondBehavior').each(function () {
      if ($(this).visible()) {
        $(this).css('background', 'green');
      }
      else {
        $(this).css('background', 'yellow').show();
      }
    });
  }
};*/

/*function mymodule_page_attachments(array &$page) {
  $computedData = \Drupal\mymodule\MyData::getData();
  $page['#attached']['drupalSettings']['mymoduleComputedData'] = $computedData;
}
*/

(function ($, Drupal, drupalSettings) {
  /**
   * @namespace
   */
  Drupal.behaviors.tngraph = {
    attach: function (context) {
      
    	var rahulbaisanevar = drupalSettings.tn_graph.test;
		console.log(rahulbaisanevar);
		var users = drupalSettings.tn_graph.users;
		console.log(users);
      var options = {
  fullWidth: true,
  axisY: {
    onlyInteger: true
  },
  plugins: [
    Chartist.plugins.tooltip()
  ],
  showArea: true,
};

	var chart = new Chartist.Line('.ct-chart', {
  /*labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],*/
  /*series: [
    [{meta: 'Rahul', value: 1 }, 2, 3, 2, 4, 2, 3, 5, 4, 3]
  ]*/
  labels: users,
  series: [
    rahulbaisanevar
  ]
},options);















    }
  };
})(jQuery, Drupal, drupalSettings);