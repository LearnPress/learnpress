<?php
/**
 * Question options template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-options">
    <div class="quiz-question-options">
        <div>
            <div class="postbox">
                <h2 class="hndle"><span>Settings</span></h2>
                <div class="inside">
                    <div>
                        <div v-for="(option, key) in options" class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for="">{{option.label}}</label>
                            </div>
                            <div class="rwml-input" style="clear: both;">
                                <div v-if="checkInputField(option.type, 'textarea')">
                                    <textarea name="" id="" cols="60" rows="3" class="rwmb-textarea large-text">{{option.value}}</textarea>
                                </div>
                                <div v-if="checkInputField(option.type, 'number')">
                                    <input type="number" :value="option.value">
                                </div>
                                <p class="description">{{option.desc}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-question-options', {
                template: '#tmpl-lp-question-options',
                props: ['options'],
                methods: {
                    checkInputField: function (type, field) {
                        return type === field;
                    }
                }
            }
        )

    })(Vue, LP_Quiz_Store);
</script>
