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
  /*labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  series: [
    [{meta: 'user_name', value: 1 }, 2, 3, 2, 4, 2, 3, 5, 4, 3]
  ]*/
  labels: users,
  series: [
    rahulbaisanevar
  ]
},options);















    }
  };
})(jQuery, Drupal, drupalSettings);