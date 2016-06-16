#ifndef FP_UTIL_SINGLETON_H_
#define FP_UTIL_SINGLETON_H_

template<typename T>
class Singleton
{
public:
    static T& GetInstance()
    {
        static T t;
        return t;
    }

    Singleton(Singleton const&) = delete;
    void operator=(Singleton const&) = delete;

protected:
    Singleton() {}

};

#endif

