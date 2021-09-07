((blocks, editor, element) => {
  const el = element.createElement;

  blocks.registerBlockType('tfn/legacy-poll', {
    title: 'TFN Legacy Poll',
    icon: 'smiley',
    category: 'common',
    attributes: {
      title: { type: 'string' },
      options: {
        type: 'array',
        default: []
      }
    },
    edit: function(props) {
      return el('div', {},
        el('h3', {}, props.attributes.title),
        el('div', { className: 'results' },
          ...props.attributes.options.map((item) => {
            return el('dl', { className: 'result' },
              el('dt', { className: 'answer' }, item.option),
              el('dd', { className: 'votes' }, item.votes )
            );
          })
        )
      );
    },
    save: function(props) {
      return el('div', { className: 'legacy-poll' },
        el('h3', {}, props.attributes.title),
        el('div', { className: 'results' },
          ...props.attributes.options.map((item) => {
            return el('dl', { className: 'result' },
              el('dt', { className: 'answer' }, item.option),
              el('dd', { className: 'votes' }, item.votes )
            );
          })
        )
      );
    }
  })
})(window.wp.blocks, window.wp.editor, window.wp.element);