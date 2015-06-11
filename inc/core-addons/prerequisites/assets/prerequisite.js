/**
 * Created by foobla on 4/7/2015.
 */

jQuery(document).ready(function($){
   $('.course-free-button').unbind("click",function(){});
   $('.btn-take-course').click(function($event){
       $event.preventDefault();
       //$('.error-notice').html('You have to complete prereqisite courses before taking this course');
       jAlert('You have to complete prerequisite courses before taking this course');
   })
});