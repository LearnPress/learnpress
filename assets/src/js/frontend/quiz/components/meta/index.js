import {Component} from '@wordpress/element';

class Meta extends Component{
    render(){
        return <div>
            <ul className="quiz-intro">
                <li>
                    <label>Attempts allowed</label>
                    <span>3</span>
                </li>
                <li>
                    <label>Duration</label>
                    <span>00:10:00</span>
                </li>
                <li>
                    <label>Passing grade</label>
                    <span>90%</span>
                </li>
                <li>
                    <label>Questions</label>
                    <span>5</span>
                </li>
            </ul>
        </div>
    }
}

export default Meta;