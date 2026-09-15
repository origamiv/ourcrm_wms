<script setup lang="ts">
import { computed, ref, nextTick, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
const page = usePage<any>();
interface RibbonTab {
    label: string;
    url: string;
    component: string;
    icon: string;
    children?: RibbonTab[];
}
const props = defineProps<{ tabs: RibbonTab[]; label: string }>();
const expanded = ref<string | null>(null);
const submenu = ref<HTMLElement | null>(null);
const tabsNav = ref<HTMLElement | null>(null);
const menuStyle = ref({ left: "0px", top: "0px", maxHeight: "320px" });
let anchor: HTMLElement | null = null;
const scrollStorageKey = computed(() => `ribbon-tabs-scroll:${props.label}`);
function saveTabsScroll() {
    if (!tabsNav.value) return;
    sessionStorage.setItem(scrollStorageKey.value, String(tabsNav.value.scrollLeft));
}
function closeMenu(focus = false) {
    expanded.value = null;
    if (focus) anchor?.focus();
}
function positionMenu() {
    if (!anchor || !expanded.value) return;
    const rect = anchor.getBoundingClientRect();
    menuStyle.value = {
        left: `${Math.max(8, Math.min(rect.left, window.innerWidth - 208))}px`,
        top: `${rect.bottom + 4}px`,
        maxHeight: `${Math.max(80, window.innerHeight - rect.bottom - 12)}px`,
    };
}
async function toggle(tab: RibbonTab, event: Event, focusFirst = false) {
    if (expanded.value === tab.url && !focusFirst) {
        closeMenu();
        return;
    }
    anchor = event.currentTarget as HTMLElement;
    expanded.value = tab.url;
    positionMenu();
    await nextTick();
    if (focusFirst)
        submenu.value?.querySelector<HTMLAnchorElement>("a")?.focus();
}
function outside(event: PointerEvent) {
    if (
        !submenu.value?.contains(event.target as Node) &&
        !anchor?.contains(event.target as Node)
    )
        closeMenu();
}
function keydown(event: KeyboardEvent) {
    if (!expanded.value) return;
    if (event.key === "Escape") {
        event.preventDefault();
        closeMenu(true);
    }
    if (
        !["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key) ||
        !submenu.value?.contains(document.activeElement)
    )
        return;
    event.preventDefault();
    const links = Array.from(
        submenu.value.querySelectorAll<HTMLAnchorElement>("a"),
    );
    const current = links.indexOf(document.activeElement as HTMLAnchorElement);
    const index =
        event.key === "Home"
            ? 0
            : event.key === "End"
              ? links.length - 1
              : (current +
                    (event.key === "ArrowDown" ? 1 : -1) +
                    links.length) %
                links.length;
    links[index]?.focus();
}
onMounted(() => {
    nextTick(() => {
        const saved = Number(sessionStorage.getItem(scrollStorageKey.value));
        if (tabsNav.value && Number.isFinite(saved)) tabsNav.value.scrollLeft = saved;
    });
    document.addEventListener("pointerdown", outside);
    document.addEventListener("keydown", keydown);
    window.addEventListener("resize", positionMenu);
    window.addEventListener("scroll", positionMenu, true);
});
onUnmounted(() => {
    document.removeEventListener("pointerdown", outside);
    document.removeEventListener("keydown", keydown);
    window.removeEventListener("resize", positionMenu);
    window.removeEventListener("scroll", positionMenu, true);
});
function active(tab: RibbonTab): boolean {
    return (
        page.url.split("?")[0] === tab.url ||
        page.url.startsWith(tab.url + "/") ||
        !!tab.children?.some(active)
    );
}
function open(tab: RibbonTab) {
    closeMenu();
    if (navigator.onLine) router.visit(tab.url);
    else
        router.push({
            url: tab.url,
            component: tab.component,
            props: { ...page.props, companyScope: null, clientScope: null },
        });
}
</script>
<template>
    <div class="ribbon-group">
        <nav ref="tabsNav" class="module-tabs" :aria-label="props.label" @scroll="saveTabsScroll">
            <template v-for="tab in props.tabs" :key="tab.url">
                <button
                    v-if="tab.children"
                    class="module-tab"
                    :class="{ active: active(tab) || expanded === tab.url }"
                    :aria-expanded="expanded === tab.url"
                    aria-controls="reference-submenu"
                    @click="toggle(tab, $event)"
                    @keydown.down.stop.prevent="toggle(tab, $event, true)"
                >
                    <img
                        :src="`/design/crm/${tab.icon}.svg`"
                        alt=""
                        width="20"
                        height="20"
                    /><span>{{ tab.label }} ▾</span>
                </button>
                <a
                    v-else
                    :href="tab.url"
                    class="module-tab"
                    :class="{ active: active(tab) }"
                    :aria-current="active(tab) ? 'page' : undefined"
                    @click.prevent="open(tab)"
                >
                    <img
                        :src="`/design/crm/${tab.icon}.svg`"
                        alt=""
                        width="20"
                        height="20"
                    /><span>{{ tab.label }}</span>
                </a>
            </template>
        </nav>
        <Teleport to="body"
            ><nav
                v-if="expanded"
                ref="submenu"
                :style="menuStyle"
                id="reference-submenu"
                class="ribbon-dropdown"
                aria-label="Справочники"
            >
                <a
                    v-for="tab in props.tabs.find((tab) => tab.url === expanded)
                        ?.children"
                    :key="tab.url"
                    :href="tab.url"
                    class="dropdown-item"
                    :class="{ active: active(tab) }"
                    :aria-current="active(tab) ? 'page' : undefined"
                    @click.prevent="open(tab)"
                >
                    <img
                        :src="`/design/crm/${tab.icon}.svg`"
                        alt=""
                        width="20"
                        height="20"
                    /><span>{{ tab.label }}</span>
                </a>
            </nav></Teleport
        >
    </div>
</template>
<style scoped>
.ribbon-group {
    min-width: 0;
}
.ribbon-dropdown {
    position: fixed;
    z-index: 100;
    width: 200px;
    max-width: calc(100vw - 16px);
    padding: 5px;
    border: 1px solid #a8d4a9;
    border-radius: 8px;
    background: white;
    box-shadow: 0 6px 18px #0c456726;
    overflow-y: auto;
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 5px;
    color: #0c1821;
    font-size: 14px;
    text-decoration: none;
}
.dropdown-item:hover,
.dropdown-item:focus-visible,
.dropdown-item.active {
    background: #e1f3e7;
    color: #1e892f;
}
.dropdown-item img {
    width: 18px;
    height: 18px;
    object-fit: contain;
    filter: brightness(0) saturate(100%) invert(38%) sepia(69%) saturate(636%)
        hue-rotate(79deg) brightness(94%) contrast(91%);
}
.module-tabs {
    display: flex;
    width: max-content;
    max-width: 100%;
    flex-wrap: nowrap;
    gap: 0;
    border: 4px solid #a8d4a9;
    border-radius: 10px;
    overflow-x: auto;
    background: #e1f3e7;
}
.module-tab {
    flex: 0 0 auto;
    min-width: 63px;
    padding: 6px;
    gap: 6px;
    border: 0;
    border-right: 4px solid #a8d4a9;
    border-radius: 0;
    color: #0c1821;
    font-size: 12px;
    line-height: 1.3;
    text-decoration: none;
    white-space: nowrap;
}
.module-tab:last-child {
    border-right: 0;
}
.module-tab:hover {
    background: #cdebd7;
}
.module-tab.active {
    background: #1e892f;
    color: #e1f3e7;
}
.module-tab img {
    width: 20px;
    height: 20px;
    object-fit: contain;
    filter: brightness(0) saturate(100%) invert(38%) sepia(69%) saturate(636%)
        hue-rotate(79deg) brightness(94%) contrast(91%);
}
.module-tab.active img {
    filter: brightness(0) invert(1);
}
.module-tab:focus-visible {
    outline: 2px solid #2274a5;
    outline-offset: -2px;
}
@media (max-width: 767px) {
    .module-tabs {
        border-width: 3px;
    }
    .module-tab {
        min-width: 50px;
        font-size: 10px;
        border-right-width: 3px;
    }
}
</style>
