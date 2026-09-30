import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const Font = Quill.import('attributors/class/font')
Font.whitelist = [
  false, 'arial', 'sans-serif', 'serif', 'monospace',
  'times-new-roman', 'courier-new', 'georgia',
  'verdana', 'tahoma', 'trebuchet-ms', 'inter',
]
Quill.register(Font, true)

window.Quill = Quill
