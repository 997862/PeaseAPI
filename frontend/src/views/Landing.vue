<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-3">
            <img :src="logoUrl" alt="PeaseAPI" class="h-10 w-10 rounded-xl shadow-lg" />
            <span class="text-xl font-bold text-gray-900">PeaseAI</span>
          </div>
          <div class="flex items-center gap-4">
            <a href="/user/" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition">登录</a>
            <a href="/user/register.html" class="text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 px-4 py-2 rounded-lg shadow-sm hover:shadow-md transition">免费注册</a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 text-primary-700 text-sm font-medium mb-8">
          <span class="flex h-2 w-2 rounded-full bg-primary-600 animate-pulse"></span>
          新一代 AI API 管理平台
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">
          一个平台，接入<span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600">所有主流 AI 模型</span>
        </h1>
        <p class="mt-6 text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed">
          统一 API 入口，支持 OpenAI、Claude、Gemini、Qwen、DeepSeek 等十多种主流 AI 模型。<br/>Token 管理、渠道监控、使用统计，一站搞定。
        </p>
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="/user/register.html" class="w-full sm:w-auto px-8 py-3.5 text-base font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">立即开始使用 →</a>
          <a href="/user/" class="w-full sm:w-auto px-8 py-3.5 text-base font-semibold text-gray-700 bg-white hover:bg-gray-50 rounded-xl shadow-md hover:shadow-lg border border-gray-200 transition-all">登录 PeaseAPI</a>
        </div>
      </div>
    </section>

    <!-- AI Models -->
    <section class="pb-16 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <p class="text-center text-sm font-medium text-gray-400 uppercase tracking-wider mb-8">支持的 AI 模型与渠道</p>
        <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-8">
          <div v-for="logo in logos" :key="logo.name" class="flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-1 transition-all p-3">
            <img :src="'https://upload.o51.com/ico-svg/ai/' + logo.file" :alt="logo.name" class="w-full h-full object-contain" />
          </div>
        </div>
        <p class="text-center text-sm text-gray-400 mt-6">更多模型持续接入中...</p>
      </div>
    </section>

    <!-- Features -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900">强大功能，开箱即用</h2>
          <p class="mt-4 text-lg text-gray-500">满足从个人开发者到企业团队的全方位需求</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
          <div class="group p-8 rounded-2xl bg-gradient-to-br from-primary-50 to-white border border-primary-100 hover:shadow-lg transition-all">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 mb-6">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">Token 管理</h3>
            <p class="text-gray-600 leading-relaxed">灵活创建、分发和监控 API Token，支持自定义配额、有效期和权限控制。</p>
          </div>
          <div class="group p-8 rounded-2xl bg-gradient-to-br from-indigo-50 to-white border border-indigo-100 hover:shadow-lg transition-all">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 mb-6">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">使用统计</h3>
            <p class="text-gray-600 leading-relaxed">实时查看 API 调用量、费用支出和性能指标，数据可视化一目了然。</p>
          </div>
          <div class="group p-8 rounded-2xl bg-gradient-to-br from-emerald-50 to-white border border-emerald-100 hover:shadow-lg transition-all">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 mb-6">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">渠道监控</h3>
            <p class="text-gray-600 leading-relaxed">多 API 渠道负载均衡与健康检测，自动切换故障节点，保障服务稳定性。</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-primary-600 to-indigo-600">
      <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-3xl font-bold text-white mb-6">准备好开始了吗？</h2>
        <p class="text-lg text-primary-100 mb-10">免费注册，立即体验统一的 AI API 管理能力</p>
        <a href="/user/register.html" class="inline-flex items-center px-8 py-4 text-base font-semibold text-primary-600 bg-white hover:bg-primary-50 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
          免费注册账号<svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
        </a>
      </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 border-t border-gray-100">
      <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
          <div class="flex items-center gap-3">
            <img :src="logoUrl" alt="PeaseAPI" class="h-8 w-8 rounded-lg" />
            <span class="text-sm font-semibold text-gray-900">PeaseAPI (豌豆API)</span>
          </div>
          <p class="text-sm text-gray-500">&copy; 2026 PeaseAPI Team. All rights reserved.</p>
        </div>
        <div class="border-t border-gray-200 pt-6">
          <div class="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-gray-600">
            <span>💬 交流与反馈</span>
            <span>QQ 交流群：10662299</span>
            <span>问题反馈：<a href="https://www.peaseapi.net" class="text-primary-600 hover:underline">www.peaseapi.net</a></span>
            <span>商务合作：<a href="mailto:791777@gmail.com" class="text-primary-600 hover:underline">791777@gmail.com</a></span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
const logoUrl = 'https://upload.o51.com/ico-svg/ico/favicon.png'
const logos = [
  { name: 'OpenAI', file: 'OpenAI.svg' },
  { name: 'Claude', file: 'Claude.svg' },
  { name: 'Gemini', file: 'gemini-ai.svg' },
  { name: 'Qwen', file: 'Qwen3.svg' },
  { name: 'DeepSeek', file: 'deepseek1.svg' },
  { name: 'Moonshot', file: 'logo-moonshot.svg' },
  { name: 'Mistral', file: 'Mistral.svg' },
  { name: '智谱 AI', file: 'a-icon27_AI-zhipu.svg' },
]
</script>
