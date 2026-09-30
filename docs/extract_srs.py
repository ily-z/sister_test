import zipfile, re
path = 'rental mobil.txt'
with zipfile.ZipFile(path) as z:
    names = z.namelist()
    print('names:', names[:20])
    if 'word/document.xml' in names:
        xml = z.read('word/document.xml').decode('utf-8')
        text = ' '.join(re.findall(r'>([^<>]+)<', xml))
        print(text[:4000])
    else:
        print('document.xml missing')
