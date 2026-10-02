import fs from 'node:fs';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import * as lucide from 'lucide-react';

const aliases = [...fs.readFileSync('config/ui-icons.php', 'utf8').matchAll(/'([^']+)' => '([^']+)'/g)];
const names = [...new Set([...aliases.map(match => match[2]), 'sun', 'moon', 'check-circle-2', 'inbox'])];
const symbols = names.map(name => {
    const exportName = name.split('-').map(part => part[0].toUpperCase() + part.slice(1)).join('');
    if (!lucide[exportName]) throw new Error('Unknown Lucide icon: ' + name);
    const svg = renderToStaticMarkup(React.createElement(lucide[exportName]));
    const content = svg.replace(/^<svg[^>]*>/, '').replace(/<\/svg>$/, '');
    return `<symbol id="${name}" viewBox="0 0 24 24">${content}</symbol>`;
});
fs.mkdirSync('public/icons/lucide', {recursive: true});
fs.writeFileSync('public/icons/lucide/sprite.svg', '<svg xmlns="http://www.w3.org/2000/svg">' + symbols.join('') + '</svg>\n');
console.log('Lucide sprite: ' + names.length + ' icons');
