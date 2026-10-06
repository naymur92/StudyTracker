#!/usr/bin/env node
/**
 * Content check for the learning-science catalog and the User Guide.
 *
 * Fails (exit 1) when:
 *  - a technique, algorithm or guide section cites an unknown reference id;
 *  - a reference is never cited, or is not marked `verified: true`;
 *  - a technique/algorithm lacks an evidence label, two benefits or a reference;
 *  - a navigation item has no User Guide section;
 *  - a guide section points at an unknown technique or algorithm.
 *
 * Runs as `npm run check:content` and automatically before `npm run build`.
 */
import { references, techniques, algorithms, EVIDENCE } from '../resources/js/content/learningScience.js'
import { guideSections } from '../resources/js/content/userGuide.js'
import { mainNavigation } from '../resources/js/config/navigation.js'

const errors = []
const fail = (message) => errors.push(message)

const cited = new Set()
const cite = (owner, ids = []) => {
    for (const id of ids) {
        if (!references[id]) fail(`${owner} cites unknown reference "${id}"`)
        cited.add(id)
    }
}

for (const [kind, entries] of [['technique', techniques], ['algorithm', algorithms]]) {
    const seen = new Set()
    for (const entry of entries) {
        const owner = `${kind} "${entry.id}"`
        if (seen.has(entry.id)) fail(`duplicate ${owner}`)
        seen.add(entry.id)
        if (!EVIDENCE[entry.evidence]) fail(`${owner} has no valid evidence label`)
        if (!Array.isArray(entry.benefits) || entry.benefits.length < 2) fail(`${owner} needs at least two benefits`)
        if (!Array.isArray(entry.refs) || entry.refs.length < 1) fail(`${owner} needs at least one reference`)
        cite(owner, entry.refs)
    }
}

const techniqueIds = new Set(techniques.map((t) => t.id))
const algorithmIds = new Set(algorithms.map((a) => a.id))
const sectionIds = new Set(guideSections.map((s) => s.id))

for (const section of guideSections) {
    const owner = `guide section "${section.id}"`
    for (const id of section.techniques ?? []) if (!techniqueIds.has(id)) fail(`${owner} uses unknown technique "${id}"`)
    for (const id of section.algorithms ?? []) if (!algorithmIds.has(id)) fail(`${owner} uses unknown algorithm "${id}"`)
    for (const tip of section.tips ?? []) cite(owner, tip.refs ?? [])
}

for (const item of mainNavigation) {
    if (!item.guideSection) fail(`navigation item "${item.name}" has no guideSection`)
    else if (!sectionIds.has(item.guideSection)) fail(`navigation item "${item.name}" has no User Guide section "${item.guideSection}"`)
}

for (const [id, ref] of Object.entries(references)) {
    if (!cited.has(id)) fail(`reference "${id}" is never cited`)
    if (ref.verified !== true) fail(`reference "${id}" is not verified against its publication record`)
    if (!ref.apa || !ref.short) fail(`reference "${id}" needs "apa" and "short" text`)
}

if (errors.length) {
    console.error(`Learning content check failed (${errors.length}):`)
    for (const e of errors) console.error(`  - ${e}`)
    process.exit(1)
}

console.log(
    `Learning content OK: ${techniques.length} techniques, ${algorithms.length} algorithms, ` +
        `${Object.keys(references).length} references, ${guideSections.length} guide sections.`,
)
