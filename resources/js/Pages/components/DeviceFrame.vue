<script setup>
import { computed } from 'vue';

const props = defineProps({
  device: { type: Object, required: true },
  edgeToEdge: { type: Boolean, default: false },
});

const BEZEL = {
  android: { short: 10, longStart: 14, longEnd: 18, radius: 28 },
  iphone: { short: 12, longStart: 18, longEnd: 22, radius: 44 },
  tablet: { short: 18, longStart: 22, longEnd: 26, radius: 20 },
};

const isLandscape = computed(
  () => props.device.orientation === 'landscape' || props.device.width > props.device.height
);

const platform = computed(() => props.device.platform ?? 'android');
const bezel = computed(() => BEZEL[platform.value] ?? BEZEL.android);

const screenW = computed(() => props.device.width);
const screenH = computed(() => props.device.height);

/** Outer frame matches viewport + bezels (wide when landscape, tall when portrait) */
const stageStyle = computed(() => {
  if (isLandscape.value) {
    return {
      width: `${screenW.value + bezel.value.longStart + bezel.value.longEnd}px`,
      height: `${screenH.value + bezel.value.short * 2}px`,
    };
  }
  return {
    width: `${screenW.value + bezel.value.short * 2}px`,
    height: `${screenH.value + bezel.value.longStart + bezel.value.longEnd}px`,
  };
});

const screenStyle = computed(() => ({
  width: `${screenW.value}px`,
  height: `${screenH.value}px`,
}));

const chromeStartStyle = computed(() =>
  isLandscape.value
    ? { width: `${bezel.value.longStart}px`, flexShrink: '0', alignSelf: 'center' }
    : { height: `${bezel.value.longStart}px`, width: '100%', flexShrink: '0' }
);

const chromeEndStyle = computed(() =>
  isLandscape.value
    ? { width: `${bezel.value.longEnd}px`, flexShrink: '0', alignSelf: 'center' }
    : { height: `${bezel.value.longEnd}px`, width: '100%', flexShrink: '0' }
);
</script>

<template>
  <div class="device-stage flex items-center justify-center shrink-0" :style="stageStyle">
    <div
      class="device-chassis relative flex shadow-2xl transition-all duration-300 w-full h-full"
      :class="[
        `device-chassis--${platform}`,
        isLandscape ? 'device-chassis--landscape flex-row items-center justify-center' : 'flex-col items-center',
      ]"
    >
      <!-- Android -->
      <template v-if="platform === 'android'">
        <div
          class="device-chrome shrink-0 flex justify-center items-center self-center"
          :class="isLandscape ? 'flex-col' : 'w-full items-end'"
          :style="chromeStartStyle"
        >
          <div class="android-camera-hole" />
        </div>
        <div
          class="device-screen device-screen--fit rounded-2xl overflow-hidden bg-black shrink-0 self-center"
          :class="{ 'device-screen--edge': edgeToEdge }"
          :style="screenStyle"
        >
          <slot />
        </div>
        <div
          class="device-chrome shrink-0 flex justify-center items-center self-center"
          :class="isLandscape ? 'flex-col' : 'w-full'"
          :style="chromeEndStyle"
        >
          <div class="android-nav-bar" :class="{ 'android-nav-bar--landscape': isLandscape }" />
        </div>
      </template>

      <!-- iPhone -->
      <template v-else-if="platform === 'iphone'">
        <div
          class="device-chrome shrink-0 flex justify-center self-center"
          :class="isLandscape ? 'flex-col items-center' : 'w-full items-end'"
          :style="chromeStartStyle"
        >
          <div class="iphone-notch" :class="{ 'iphone-notch--landscape': isLandscape }">
            <div class="iphone-notch-camera" />
          </div>
        </div>
        <div
          class="device-screen device-screen--fit rounded-2xl overflow-hidden bg-black shrink-0 self-center"
          :class="{ 'device-screen--edge': edgeToEdge }"
          :style="screenStyle"
        >
          <slot />
        </div>
        <div
          class="device-chrome shrink-0 flex justify-center items-center self-center"
          :class="isLandscape ? 'flex-col' : 'w-full'"
          :style="chromeEndStyle"
        >
          <div class="iphone-home-indicator" :class="{ 'iphone-home-indicator--landscape': isLandscape }" />
        </div>
        <div class="iphone-power-btn" :class="{ 'iphone-power-btn--landscape': isLandscape }" />
      </template>

      <!-- Tablet -->
      <template v-else>
        <div
          class="device-chrome shrink-0 flex justify-center items-center self-center"
          :class="isLandscape ? 'flex-col' : 'w-full'"
          :style="chromeStartStyle"
        >
          <div class="tablet-camera-dot" />
        </div>
        <div
          class="device-screen device-screen--fit rounded-2xl overflow-hidden bg-black shrink-0 self-center border-[3px] border-gray-600"
          :class="{ 'device-screen--edge': edgeToEdge }"
          :style="screenStyle"
        >
          <slot />
        </div>
        <div
          class="device-chrome shrink-0 flex justify-center items-center self-center"
          :class="isLandscape ? 'flex-col' : 'w-full'"
          :style="chromeEndStyle"
        >
          <div v-if="!isLandscape" class="tablet-home-button" />
          <div v-else class="tablet-home-indicator" />
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.device-chassis--android {
  background: linear-gradient(145deg, #2a2a2e 0%, #1a1a1d 100%);
  border-radius: 28px;
  box-shadow:
    0 0 0 2px #3f3f46,
    0 24px 48px rgba(0, 0, 0, 0.55),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.device-chassis--iphone {
  background: linear-gradient(160deg, #3d3d42 0%, #1c1c1e 55%, #0a0a0b 100%);
  border-radius: 44px;
  box-shadow:
    0 0 0 3px #52525b,
    0 28px 56px rgba(0, 0, 0, 0.6),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.device-chassis--tablet {
  background: linear-gradient(145deg, #3f3f46 0%, #27272a 100%);
  border-radius: 20px;
  box-shadow:
    0 0 0 3px #52525b,
    0 32px 64px rgba(0, 0, 0, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.device-chassis--landscape {
  box-sizing: border-box;
}

.android-camera-hole {
  width: 10px;
  height: 10px;
  border-radius: 9999px;
  background: #0a0a0a;
  box-shadow: inset 0 0 0 2px #27272a;
}

.android-nav-bar {
  width: 96px;
  height: 4px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.35);
}

.android-nav-bar--landscape {
  width: 4px;
  height: 96px;
}

.iphone-notch {
  width: 112px;
  height: 26px;
  background: #0a0a0a;
  border-radius: 0 0 18px 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.iphone-notch--landscape {
  width: 26px;
  height: 112px;
  border-radius: 0 18px 18px 0;
}

.iphone-notch-camera {
  width: 8px;
  height: 8px;
  border-radius: 9999px;
  background: #1e293b;
}

.iphone-home-indicator {
  width: 120px;
  height: 5px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.4);
}

.iphone-home-indicator--landscape {
  width: 5px;
  height: 120px;
}

.tablet-camera-dot {
  width: 8px;
  height: 8px;
  border-radius: 9999px;
  background: #18181b;
  box-shadow: inset 0 0 0 1px #3f3f46;
}

.tablet-home-button {
  width: 36px;
  height: 36px;
  border-radius: 9999px;
  border: 2px solid rgba(255, 255, 255, 0.25);
}

.tablet-home-indicator {
  width: 5px;
  height: 140px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.35);
}

.iphone-power-btn {
  position: absolute;
  right: -3px;
  top: 28%;
  width: 3px;
  height: 48px;
  background: #52525b;
  border-radius: 0 2px 2px 0;
}

.iphone-power-btn--landscape {
  top: -3px;
  right: 28%;
  width: 48px;
  height: 3px;
  border-radius: 2px 2px 0 0;
}

.android-volume-rocker {
  position: absolute;
  left: -3px;
  top: 22%;
  width: 3px;
  height: 56px;
  background: #3f3f46;
  border-radius: 2px 0 0 2px;
}

.android-volume-rocker--landscape {
  left: 22%;
  top: -3px;
  width: 56px;
  height: 3px;
  border-radius: 2px 2px 0 0;
}

.device-screen--fit {
  display: flex;
  flex-direction: column;
}

.device-screen--edge {
  align-items: stretch;
  justify-content: flex-start;
}

.device-screen :deep(> *) {
  width: 100% !important;
  max-width: 100% !important;
  height: 100% !important;
  min-height: 0 !important;
  flex: 1 1 auto;
  border-radius: 1rem;
  box-sizing: border-box;
}

.device-screen--edge :deep(> *) {
  border-radius: 1rem;
  padding-left: 0 !important;
  padding-right: 0 !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  align-self: stretch;
}
</style>
