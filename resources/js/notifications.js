const root=document.querySelector('[data-operational-notifications]');
if(root){
    const panel=root.querySelector('dialog'),list=root.querySelector('[data-notification-list]'),status=root.querySelector('[data-notification-status]'),badge=root.querySelector('[data-notification-badge]'),more=root.querySelector('[data-notification-more]'),open=root.querySelector('[data-notification-open]');
    let next=null,timer=null,inFlight=false,stopped=false;
    const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content;
    async function request(url,method='GET'){
        const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),10000);
        try{const response=await fetch(url,{method,credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf()},signal:controller.signal});if(response.status===401||response.status===403){stopped=true;clearTimeout(timer);const error=new Error('access');error.status=response.status;throw error;}if(!response.ok)throw new Error('unavailable');return await response.json();}finally{clearTimeout(timeout);}
    }
    const node=(tag,text,className)=>{const e=document.createElement(tag);e.textContent=text??'';if(className)e.className=className;return e;};
    function showError(error){status.textContent=error.status===401?root.dataset.signIn:error.status===403?root.dataset.denied:root.dataset.error;if(error.status===401){const login=node('a',root.dataset.signIn,'underline ml-2');login.href=root.dataset.login;status.append(login);}}
    function render(data,append){
        const focused=document.activeElement?.dataset?.notificationRead;
        badge.textContent=data.unread>99?'99+':String(data.unread);badge.classList.toggle('hidden',!data.unread);open.setAttribute('aria-label',root.dataset.title+': '+data.unread);
        if(!append)list.replaceChildren();
        for(const row of data.rows){const item=node('li',null,'qpos-notification-item'+(row.read?'':' is-unread'));let title=node('strong',row.title);if(row.url){const target=new URL(row.url,location.origin);if(target.origin===location.origin){title=node('a',row.title);title.href=target.href;title.className='font-semibold underline';}}
            item.append(title,node('p',[row.reference,row.shop_id?'#'+row.shop_id:null].filter(Boolean).join(' · '),'text-sm text-qpos-muted'));
            if(!row.read){const read=node('button',root.dataset.readLabel,'qpos-button qpos-button-sm qpos-button-ghost mt-2');read.type='button';read.dataset.notificationRead=row.id;read.addEventListener('click',async()=>{read.disabled=true;try{await request(root.dataset.readBase+'/'+encodeURIComponent(row.id)+'/read','POST');await load(false);}catch{status.textContent=root.dataset.error;read.disabled=false;}});item.append(read);}list.append(item);
        }
        next=data.next;more.classList.toggle('hidden',!next);status.textContent=list.children.length?'':root.dataset.empty;
        if(focused&&panel.open)(list.querySelector('[data-notification-read="'+CSS.escape(focused)+'"]')||root.querySelector('[data-notification-close]')).focus();
    }
    async function load(append=false){
        if(inFlight||document.hidden||stopped)return;
        inFlight=true;try{const url=new URL(root.dataset.feed);if(append&&next)url.searchParams.set('before',next);render(await request(url.href),append);}catch(error){showError(error);}finally{inFlight=false;}
    }
    function schedule(){clearTimeout(timer);if(!document.hidden&&!stopped)timer=setTimeout(async()=>{await load();schedule();},30000);}
    open.addEventListener('click',()=>{panel.showModal();load();});root.querySelector('[data-notification-close]').addEventListener('click',()=>panel.close());panel.addEventListener('close',()=>open.focus());
    root.querySelector('[data-notification-read-all]').addEventListener('click',async event=>{const control=event.currentTarget;control.disabled=true;try{await request(root.dataset.readAll,'POST');await load();}catch{status.textContent=root.dataset.error;}finally{control.disabled=false;}});
    more.addEventListener('click',()=>load(true));
    document.addEventListener('visibilitychange',()=>{if(document.hidden)clearTimeout(timer);else{load();schedule();}});
    window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});window.addEventListener('pageshow',()=>{stopped=false;load();schedule();});
    load();schedule();
}
