const KEY = 'sipri_favoritos';

function getAll() {
    try { return JSON.parse(localStorage.getItem(KEY) ?? '{}'); }
    catch { return {}; }
}

function saveAll(data) {
    localStorage.setItem(KEY, JSON.stringify(data));
    window.dispatchEvent(new CustomEvent('sipri:favoritos', { detail: { ...data } }));
}

export function toggle(type, id, nombre = null, href = null) {
    const data = getAll();
    const list = data[type] ?? [];
    const idx = list.indexOf(id);
    const added = idx === -1;
    if (added) {
        list.push(id);
        if (nombre) {
            const nombres = data[`${type}_nombres`] ?? {};
            nombres[id] = nombre;
            data[`${type}_nombres`] = nombres;
        }
        if (href) {
            const hrefs = data[`${type}_hrefs`] ?? {};
            hrefs[id] = href;
            data[`${type}_hrefs`] = hrefs;
        }
    } else {
        list.splice(idx, 1);
    }
    data[type] = list;
    saveAll(data);
    return added;
}

export function has(type, id) {
    return (getAll()[type] ?? []).includes(id);
}

export function getList(type) {
    return getAll()[type] ?? [];
}

export function getNombre(type, id) {
    return (getAll()[`${type}_nombres`] ?? {})[id] ?? null;
}

export function getHref(type, id) {
    return (getAll()[`${type}_hrefs`] ?? {})[id] ?? null;
}

export function setList(type, list) {
    const data = getAll();
    data[type] = list;
    saveAll(data);
}

export function saveNombreHrefSilent(type, id, nombre, href = null) {
    const data = getAll();
    if (nombre) {
        const nombres = data[`${type}_nombres`] ?? {};
        nombres[id] = nombre;
        data[`${type}_nombres`] = nombres;
    }
    if (href) {
        const hrefs = data[`${type}_hrefs`] ?? {};
        hrefs[id] = href;
        data[`${type}_hrefs`] = hrefs;
    }
    localStorage.setItem(KEY, JSON.stringify(data));
}

export function savePosicion(especialidadId, posicion) {
    const data = getAll();
    const posiciones = data.buscador_posiciones ?? {};
    posiciones[especialidadId] = posicion;
    data.buscador_posiciones = posiciones;
    // No disparar evento global; es un dato de sesión silencioso
    localStorage.setItem(KEY, JSON.stringify(data));
}

export function getPosicion(especialidadId) {
    return (getAll().buscador_posiciones ?? {})[especialidadId] ?? '';
}
