/**
 * Quiz functions
 *
 * @author ThimPress
 * @version 1.1
 */
if (typeof LP == 'undefined') {
	window.LP = {};
}
;(function ($, bb, _) {
	var Quiz = function (args) {
		this.model = new Quiz.Model(args);
		this.view = new Quiz.View({
			model: this.model
		});
	}, Questions = bb.Collection.extend({
		initialize: function () {
			console.log(this.length)
		}
	});
	Quiz.View = bb.View.extend({
		initialize: function () {
			LP.log('Quiz.View.initialize');
		}
	});
	Quiz.Model = bb.Model.extend({
		questions : null,
		initialize: function () {
			this.questions = new Questions();
			this.questions.add(_.values(this.get('questions')));
		}
	});
	$.extend(LP, {
		Quiz     : Quiz,
		$quiz    : null,
		_initQuiz: function (args) {
			if (!this.$quiz || this.$quiz.model.get('id') != args.id) {
				delete this.$quiz;
				this.$quiz = new Quiz(args);
			}
			return this.$quiz;
		}
	});
})(jQuery, Backbone, _);

