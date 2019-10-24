import {Component} from '@wordpress/element';
import Quiz from '@learnpress/quiz';

import './store';

class SingleCourse extends Component{
    render(){
        return <React.Fragment>
            this is course

            <Quiz />
        </React.Fragment>
    }
}

export default SingleCourse;