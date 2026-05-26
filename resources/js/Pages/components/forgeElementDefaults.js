/** Default sizes when dragging elements onto the canvas */
export const CONTAINER_DROP_STYLE = {
  'container-v': {
    padding: '10px',
    width: '100%',
    minHeight: '96px',
    boxSizing: 'border-box',
  },
  'container-h': {
    padding: '10px',
    width: '100%',
    minHeight: '72px',
    minWidth: '160px',
    boxSizing: 'border-box',
  },
};

export function buildDragBlueprint(item) {
  const isContainer = item.type.includes('container');
  return {
    type: item.type,
    id: `el_${Date.now()}`,
    props: {
      style: isContainer
        ? { ...(CONTAINER_DROP_STYLE[item.type] ?? CONTAINER_DROP_STYLE['container-v']) }
        : { padding: '10px' },
      content: item.type === 'text' ? 'New Text' : '',
    },
    children: isContainer ? [] : null,
  };
}
