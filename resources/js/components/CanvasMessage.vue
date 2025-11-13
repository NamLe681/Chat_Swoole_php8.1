<template>
  <div class="canvas-overlay">
    <div class="canvas-header">
      <button @click="clearCanvas">🧹 Xóa</button>
      <button @click="saveDrawing">💾 Gửi</button>
      <button @click="$emit('close')">❌ Đóng</button>
    </div>
    <input type="color" v-model="strokeColor" />
    <input type="range" min="1" max="20" v-model="lineWidth" />
    <canvas ref="canvas" width="800" height="600" @mousedown="startDrawing" @mouseup="stopDrawing"
      @mousemove="draw"></canvas>
  </div>


</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue';
import { useStore } from 'vuex';

const emit = defineEmits(['close', 'draw-sent']);
const canvas = ref(null);
const store = useStore();
const strokeColor = ref('#000000');
const lineWidth = ref(2);

let ctx = null;
let drawing = false;

const startDrawing = (e) => {
  drawing = true;
  ctx.beginPath();
  ctx.moveTo(e.offsetX, e.offsetY);
};

const draw = (e) => {
  if (!drawing) return;
  ctx.lineWidth = lineWidth.value;
  ctx.strokeStyle = strokeColor.value;
  ctx.lineTo(e.offsetX, e.offsetY);
  ctx.stroke();

};

const stopDrawing = () => (drawing = false);
const clearCanvas = () => {
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
};

const saveDrawing = async () => {
  const dataUrl = canvas.value.toDataURL('image/png');
  await store.dispatch('canvasMessage', { dataUrl });
};

onMounted(() => {
  ctx = canvas.value.getContext('2d');
  // ctx.strokeStyle = '#000';
  // ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
});
</script>

<style scoped>
.canvas-overlay {
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.canvas-header {
  position: absolute;
  top: 20px;
  display: flex;
  gap: 10px;
}

.canvas-picker {
  position: absolute;
  top: 5%;
  right: 30%;
  z-index: 10000;
}

canvas {
  border: 2px solid #333;
  cursor: crosshair;
  background: white;
  border-radius: 8px;
}
</style>
