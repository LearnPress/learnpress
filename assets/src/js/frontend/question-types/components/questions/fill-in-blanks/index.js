import QuestionBase from '../../question-base';

class QuestionFillInBlanks extends QuestionBase {
    constructor() {
        super(...arguments);
    }

    componentDidMount() {
        // this.setState({
        //     optionClass: [...this.state.optionClass, "new-class"]
        // })
    }

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass, "XYZ"];
        if (!answered && this.maybeShowCorrectAnswer()) {

            if (option.isTrue === 'yes') {
                optionClass.push('answer-correct');
                (answered === option.value) && optionClass.push('answered-correct');
            } else {
                (answered === option.value) && optionClass.push('answered-wrong');
            }
        }

        return optionClass;
    };

    getPassageContent = () => {
        const {
            question: {
                options,
                blankFillsStyle,
                blanksStyle
            }
        } = this.props;

        if (!options) {
            return '';
        }

        const preview = options.map((answer) => {
            const blanks = [];//this.getBlanks(answer.text);
            const blank = blanks ? blanks[0] : {};

            let html = '';

            if (blank && blank.words.length) {
                html = '<span class="blank-input"></span>';

                if (blank.words.length > 1) {
                    switch (blankFillsStyle) {
                        case 'select':
                            html += '<select>' + blank.words.map((word) => {
                                    return `<option value=${word}>${word}</option>`
                                }).join('') + '</select>';

                            break;
                        case 'enumeration':
                            html += '(' + blank.words.map((word) => {
                                    return `<code>${word}</code>`
                                }).join(', ') + ')';
                            break;
                    }
                }

                if (blank.tip) {
                    html += '?';
                }
            }

            return ('' + answer.text).replace(/\{\{(.*)\}\}/, html)

        }).join(blanksStyle === 'paragraphs' ? '</div><div>' : (blanksStyle === 'ordered' ? '</li><li>' : ' '));

        return blanksStyle === 'paragraphs' ? `<div>${preview}</div>` : (blanksStyle === 'ordered' ? `<ol><li>${preview}</li></ol>` : preview)
    };

    setBlankWord = (blank, word) => (event) => {
        console.log(blank, word, event.target.value)
    }

    getBlankHtml = (blank) => {
        const {
            question: {
                blankFillsStyle,
                options
            }
        } = this.props;
        const BlankOption = (props) => {
            const len = props.blank.words.length;
            return props.blank.words.map((word, i) => {
                switch (blankFillsStyle) {
                    case 'select':
                        return <option value={ word } key={ word }>{ word }</option>
                    case 'enumeration':
                        return <React.Fragment>
                            <code key={ word } onClick={ this.setBlankWord(blank, word) }>{ word }</code>
                            {
                                i === len - 1 ? '' : ','
                            }
                        </React.Fragment>
                }
            })
        };
        const textMatch = blank.text.split(/\{\{BLANK\}\}/);

        return <React.Fragment>
            {textMatch ? textMatch[0] : ''}
            <div className="blank-input-wrap">
                {/*<input type="text" className="blank-input" onChange={ this.setBlankWord(blank) }/>*/}
                <div contentEditable={true} className="blank-input"></div>
            </div>
            { blank.tip && (
                <span>help</span>
            )}

            {
                blankFillsStyle === 'select' && <select className="blank-select" onChange={ this.setBlankWord(blank) }>
                    <BlankOption blank={ blank }/>
                </select>
            }

            {
                blankFillsStyle === 'enumeration' && <div className="blank-fills">
                    (<BlankOption blank={ blank }/>)
                </div>
            }
            {textMatch ? textMatch[1] : ''}
        </React.Fragment>
    };

    render() {
        const {
            question: {
                options,
                blankFillsStyle,
                blanksStyle
            }
        } = this.props;

        const Wrap = function (props) {
            const className = props.blanksStyle ? props.blanksStyle : ' one-paragraph';
            return props.blanksStyle === 'ordered' ?
                <ol className={ `blanks ${className}` }>{props.children}</ol> :
                <div className={ `blanks ${className}` }>{props.children}</div>;
        }

        return <React.Fragment>
            <Wrap blanksStyle={ blanksStyle }>
                {
                    this.getOptions().map((option) => {
                        const blankHtml = this.getBlankHtml(option);
                        const key = `blank-${option.uid}`;

                        return blanksStyle === 'ordered' ? <li key={ key } className="blank-block">
                            {blankHtml}
                        </li> : (blanksStyle === 'paragraphs' ?
                            <div key={ key } className="blank-block">{blankHtml}</div> : blankHtml)
                    })
                }
            </Wrap>
        </React.Fragment>
    }
}

export default QuestionFillInBlanks;