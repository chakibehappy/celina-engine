export const DEFAULT_DEVICE_PRESET_ID = 'android-portrait-360';

const withMeta = (platform, orientation, option) => ({
  ...option,
  platform,
  orientation,
});

export const DEVICE_PRESET_GROUPS = [
  {
    label: 'Android',
    platform: 'android',
    options: [
      withMeta('android', 'portrait', { id: 'android-portrait-360', label: 'Portrait — 360 × 800', width: 360, height: 800 }),
      withMeta('android', 'portrait', { id: 'android-portrait-412', label: 'Portrait — 412 × 915', width: 412, height: 915 }),
      withMeta('android', 'portrait', { id: 'android-portrait-393', label: 'Portrait — 393 × 873', width: 393, height: 873 }),
      withMeta('android', 'landscape', { id: 'android-landscape-800', label: 'Landscape — 800 × 360', width: 800, height: 360 }),
      withMeta('android', 'landscape', { id: 'android-landscape-915', label: 'Landscape — 915 × 412', width: 915, height: 412 }),
      withMeta('android', 'landscape', { id: 'android-landscape-873', label: 'Landscape — 873 × 393', width: 873, height: 393 }),
    ],
  },
  {
    label: 'iPhone',
    platform: 'iphone',
    options: [
      withMeta('iphone', 'portrait', { id: 'iphone-portrait-375', label: 'Portrait — 375 × 812', width: 375, height: 812 }),
      withMeta('iphone', 'portrait', { id: 'iphone-portrait-390', label: 'Portrait — 390 × 844', width: 390, height: 844 }),
      withMeta('iphone', 'portrait', { id: 'iphone-portrait-428', label: 'Portrait — 428 × 926', width: 428, height: 926 }),
      withMeta('iphone', 'landscape', { id: 'iphone-landscape-812', label: 'Landscape — 812 × 375', width: 812, height: 375 }),
      withMeta('iphone', 'landscape', { id: 'iphone-landscape-844', label: 'Landscape — 844 × 390', width: 844, height: 390 }),
      withMeta('iphone', 'landscape', { id: 'iphone-landscape-926', label: 'Landscape — 926 × 428', width: 926, height: 428 }),
    ],
  },
  {
    label: 'Tablet',
    platform: 'tablet',
    options: [
      withMeta('tablet', 'portrait', { id: 'tablet-portrait-768', label: 'Portrait — 768 × 1024', width: 768, height: 1024 }),
      withMeta('tablet', 'portrait', { id: 'tablet-portrait-834', label: 'Portrait — 834 × 1194', width: 834, height: 1194 }),
      withMeta('tablet', 'portrait', { id: 'tablet-portrait-800', label: 'Portrait — 800 × 1280', width: 800, height: 1280 }),
      withMeta('tablet', 'landscape', { id: 'tablet-landscape-1024', label: 'Landscape — 1024 × 768', width: 1024, height: 768 }),
      withMeta('tablet', 'landscape', { id: 'tablet-landscape-1194', label: 'Landscape — 1194 × 834', width: 1194, height: 834 }),
      withMeta('tablet', 'landscape', { id: 'tablet-landscape-1280', label: 'Landscape — 1280 × 800', width: 1280, height: 800 }),
    ],
  },
];

const presetMap = new Map(
  DEVICE_PRESET_GROUPS.flatMap((g) => g.options.map((o) => [o.id, o]))
);

export function findDevicePreset(id) {
  return presetMap.get(id) ?? presetMap.get(DEFAULT_DEVICE_PRESET_ID);
}
