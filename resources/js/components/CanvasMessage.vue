<template>
  <div class="canvas-overlay">
    <div class="canvas-header">
      <button @click="clearCanvas">🧹 Xóa</button>
      <button @click="saveDrawing">💾 Gửi</button>
      <button @click="$emit('close')">❌ Đóng</button>
    </div>

    <canvas
      ref="canvas"
      width="800"
      height="600"
      @mousedown="startDrawing"
      @mouseup="stopDrawing"
      @mousemove="draw"
    ></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from "axios";

const canvas = ref(null);
let ctx = null;
let drawing = false;

const startDrawing = (e) => {
  drawing = true;
  ctx.beginPath();
  ctx.moveTo(e.offsetX, e.offsetY);
};

const draw = (e) => {
  if (!drawing) return;
  ctx.lineTo(e.offsetX, e.offsetY);
  ctx.stroke();
};

const stopDrawing = () => {
  drawing = false;
};

const clearCanvas = () => {
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
};

const saveDrawing = () => {
  const dataUrl = canvas.value.toDataURL('image/png');
  try {
        const res = axios.post(`/api/rooms/${this.roomId}/draw`, {
          image: dataUrl,
        });
        console.log("✅ Sent drawing:", res.data);
      } catch (error) {
        console.error("❌ Send failed:", error);
      }
    $emit('draw-selected', dataUrl);
};

onMounted(() => {
  ctx = canvas.value.getContext('2d');
  ctx.strokeStyle = '#000';
  ctx.lineWidth = 2;
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
  z-index: 9999;
}

.canvas-header {
  position: absolute;
  top: 20px;
  display: flex;
  gap: 10px;
}

canvas {
  border: 2px solid #333;
  cursor: crosshair;
  background: white;
  border-radius: 8px;
}
</style>
